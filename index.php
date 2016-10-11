<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
require 'vendor/autoload.php';
require 'docdb.php';
require 'dbclass.php';

// Create and configure Slim app
$app = new \Slim\App;

//START: Log
$doc = new docdb();
$doc->make_log();
//END: Log

if (!isset($_SERVER['PHP_AUTH_USER']) || !($_SERVER['PHP_AUTH_USER'] == 'mydocapp' && $_SERVER['PHP_AUTH_PW'] == 'b68]2I3CQ*W2Mj')) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access not allowed';
    exit;
} 

/*
$app->add(new \Slim\Middleware\HttpBasicAuthentication([
    "users" => [
        "mydocapp" => "b68]2I3CQ*W2Mj"
    ]
]));
*/
//API for Registration
$app->get('/v1/Test', function ($req, $res, $args) {
	echo "OK";
});

//API for Registration
$app->post('/v1/UserReg', $UserReg);

//API for Login
$app->post('/v1/UserAuth', $UserAuth);

//API for ForgetPass
$app->post('/v1/ForgotPass', $ForgotPass);

//API for User Update
$app->post('/v1/UserUpdate', $UserUpdate);

//API for User Change Pass
$app->post('/v1/UserChangePass', $UserChangePass);

//API for MasterSync
$app->post('/v1/MasterSync', $MasterSync);

//API for RegularSync
$app->post('/v1/RegularSync', $RegularSync);

//API for AddQuery
$app->post('/v1/AddQuery', $AddQuery);

//API for AddQuery
$app->post('/v1/AddComment', $AddComment);

//API for QueList
$app->post('/v1/QueList', $QueList);

//API for CommList
$app->post('/v1/CommList', $CommList);

//API for PreTransaction
$app->post('/v1/PreTransaction', $PreTransaction);

//API for PostTransaction
$app->post('/v1/PostTransaction', $PostTransaction);

//API for GetListOfActiveDoc
$app->post('/v1/GetListOfActiveDoc', $GetListOfActiveDoc);

//API for MakeDrOnline
$app->post('/v1/MakeDrOnline', $MakeDrOnline);

//API for GetTransactionList
$app->post('/v1/GetTransactionList', $GetTransactionList);

//API for GetCountriesList
$app->post('/v1/GetCountriesList', $GetCountriesList);

// Run app
$app->run();