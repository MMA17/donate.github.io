<?php

header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Asia/Ho_Chi_Minh');
set_time_limit(0);

$seri = isset($_POST['txtseri']) ? $_POST['txtseri'] : '';
$sopin = isset($_POST['txtpin']) ? $_POST['txtpin'] : '';
$card_value = isset($_POST['card_value']) ? $_POST['card_value'] : '';
//Loai the cao (VINA, MOBI, VIETEL, VTC, GATE)
$mang = isset($_POST['chonmang']) ? $_POST['chonmang'] : '';
$user = $_POST['txtuser'];
if ($mang == 2) {
	$ten = "Mobifone";
} else if ($mang == 1) {
	$ten = "Vietel";
} else $ten = "Vinaphone"; //id = 3 la mang VINA

//Mã MerchantID dang kí trên napthengay.com
$merchant_id = '5230';
//Api email, email tai khoan dang ky tren napthengay.com
$api_email = 'nguyenhoangviet170500@gmail.com';
//mat khau di kem ma website dang kí trên  napthengay.com
$secure_code = '72e40191d152c13bfe32a9c999fc58c5';
//mã giao dịch
$trans_id = time();  //mã giao dịch do bạn gửi lên, Napthengay.com sẽ trả về 
$api_url = 'http://api.napthengay.com/v2/';


$arrayPost = array(
	'merchant_id' => intval($merchant_id),
	'api_email' => trim($api_email),
	'trans_id' => trim($trans_id),
	'card_id' => trim($mang),
	'card_value' => intval($card_value),
	'pin_field' => trim($sopin),
	'seri_field' => trim($seri),
	'algo_mode' => 'hmac'
);


$data_sign = hash_hmac('SHA1', implode('', $arrayPost), $secure_code);

$arrayPost['data_sign'] = $data_sign;

$curl = curl_init($api_url);

curl_setopt_array($curl, array(
	CURLOPT_POST => true,
	CURLOPT_HEADER => false,
	CURLINFO_HEADER_OUT => true,
	CURLOPT_TIMEOUT => 120,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_SSL_VERIFYPEER => false,
	CURLOPT_POSTFIELDS => http_build_query($arrayPost)
));

$data = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$result = json_decode($data, true);
$time = time();

if ($status == 200) {
	$amount = $result['amount'];
	switch ($amount) {
		case 10000:
			$xu = 10;
			break;
		case 20000:
			$xu = 20;
			break;
		case 50000:
			$xu = 50;
			break;
		case 100000:
			$xu = 100;
			break;
		case 200000:
			$xu = 200;
			break;
		case 500000:
			$xu = 500;
			break;
	}

	if ($result['code'] == 100) {
		$dbhost = "localhost";
		$dbuser = "hoangviet";
		$dbpass = "123456";
		$dbname = "napcard";
		$db = mysqli_connect($dbhost, $dbuser, $dbpass) or die("cant connect db");
		mysqli_select_db($db, $dbname) or die("cant select db");
		$query1 = "UPDATE users SET coins = coins + $xu WHERE login ='$user';";
		mysqli_query($db,$query1);


		// Xu ly thong tin tai day
		$file = "carddung.log";
		$fh = fopen($file, 'a') or die("cant open file");
		fwrite($fh, "Tai khoan: " . $user . ", Loai the: " . $ten . ", Menh gia: " . $amount . ", Thoi gian: " . $time);
		fwrite($fh, "\r\n");
		fclose($fh);
		echo '<script>alert("Bạn đã thanh toán thành công thẻ ' . $ten . ' mệnh giá ' . $amount . ' ");</script>';
	} else {
		echo 'Status Code:' . $result['code'] . '<hr >';
		$error = $result['msg'];
		echo $error;
		$file = "cardsai.log";
		$fh = fopen($file, 'a') or die("cant open file");
		fwrite($fh, "Tai khoan: " . $user . ", Ma the: " . $sopin . ", Seri: " . $seri . ", Noi dung loi: " . $error . ", Thoi gian: " . $time);
		fwrite($fh, "\r\n");
		fclose($fh);
		echo '<script>alert("' . $error . '!");</script>';
	}
} else {
	echo 'Status Code:' . $status . ' . Máy chủ gặp sự cố<hr >';
}
