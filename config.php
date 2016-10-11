<?php
$host = $_SERVER['HTTP_HOST'];
if($host == "localhost" || $host == "")
{
	$config['host'] = "localhost";
	$config['dbname'] = "santosh_docapp";
	$config['dbusername'] = "santosh_docapp";
	$config['dbuserpass'] = "#docapp1";
	$config['AppTitle'] = "B&J";
	$config['fromEmail'] = "support@justwebapp.com";
	$config['BccEmail'] = "mail1906@gmail.com";
}
else
{
	$config['host'] = "localhost";
	$config['dbname'] = "santosh_docapp";
	$config['dbusername'] = "santosh_docapp";
	$config['dbuserpass'] = "#docapp1";
	$config['AppTitle'] = "Orthopod Online";
	$config['fromEmail'] = "support@orthopodonline.com";
	$config['BccEmail'] = "mail1906@gmail.com";
}

define('API_ACCESS_KEY', 'AIzaSyDzy_o1UMZ8kkz3kwWbtOWWDt1Z_yI8v6g');
define('PAYMENT_VALIDITY', '7');
?>