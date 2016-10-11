<?php
$UserReg = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		//START: Validate Input
		if(!(isset($post_data['UserName'],$post_data['UserGender'],$post_data['UserMob'],$post_data['UserDOB'],$post_data['UserEmail'],$post_data['UserPass'],$post_data['UserMedicalHistory'],$post_data['UserExistingDisease'],$post_data['DeviceID'],$post_data['DeviceOS'],$post_data['PushNtfID'],$post_data['UserCountry']) && !empty($post_data['UserName']) && !empty($post_data['UserGender']) && !empty($post_data['UserMob']) && !empty($post_data['UserDOB']) && !empty($post_data['UserEmail']) && !empty($post_data['UserPass']) && !empty($post_data['DeviceID']) && !empty($post_data['DeviceOS']) && !empty($post_data['PushNtfID'])))
		{
			return json_encode(array("status"=>"A02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check user already registred
		$doc = new docdb();
		if(!$doc->is_user_not_exist($post_data['UserMob']))
		{
			return json_encode(array("status"=>"A03","message"=>"Mob Already Exist."));
		}
		//END: Check user already registred
		
		//START: Insert in Users & MobileAppIns Tables
		return $doc->create_user($post_data);
		//END: Insert in Users & MobileAppIns Tables		
		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"A04","message"=>$e->getMessage()));
	}    
};

$UserAuth = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		
		//START: Validate Input
		if(!(isset($post_data['UserMob'],$post_data['UserPass'],$post_data['DeviceID'],$post_data['DeviceOS'],$post_data['PushNtfID'],$post_data['AppInsID']) && !empty($post_data['UserMob']) && !empty($post_data['UserPass']) && !empty($post_data['DeviceID']) && !empty($post_data['DeviceOS']) && !empty($post_data['PushNtfID'])))
		{
			return json_encode(array("status"=>"B02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->get_user_det($post_data);		
		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"B03","message"=>$e->getMessage()));
	}    
};

$ForgotPass = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		
		//START: Validate Input
		if(!(isset($post_data['UserMob']) && !empty($post_data['UserMob'])))
		{
			return json_encode(array("status"=>"C02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->forget_pass($post_data);		
		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"C03","message"=>$e->getMessage()));
	}    
};

$UserUpdate = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		
		//START: Validate Input
		if(!(isset($post_data['AppInsID']) && !empty($post_data['AppInsID'])))
		{
			return json_encode(array("status"=>"D02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->user_update($post_data);		
		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"D03","message"=>$e->getMessage()));
	}    
};

$UserChangePass = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		
		//START: Validate Input
		if(!(isset($post_data['UserMob'],$post_data['UserPass'],$post_data['AppInsID']) && !empty($post_data['UserMob']) && !empty($post_data['UserPass']) && !empty($post_data['AppInsID'])))
		{
			return json_encode(array("status"=>"E02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->change_pass($post_data);		
		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"E04","message"=>$e->getMessage()));
	}    
};

$MasterSync = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		
		//START: Validate Input
		if(!(isset($post_data['AppInsID']) && !empty($post_data['AppInsID'])))
		{
			return json_encode(array("status"=>"F02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->master_sync($post_data);		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"F03","message"=>$e->getMessage()));
	}    
};

$RegularSync = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		
		//START: Validate Input
		if(!(isset($post_data['AppInsID']) && !empty($post_data['AppInsID'])))
		{
			return json_encode(array("status"=>"G02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->regular_sync($post_data);		
		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"G03","message"=>$e->getMessage()));
	}    
};

$AddQuery = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		//START: Validate Input
		if(!(isset($post_data['AppInsID'],$post_data['DocID'],$post_data['QueDesc'],$post_data['QueMedicalHistory'],$post_data['QueExistingDisease'],$post_data['Attach1'],$post_data['Attach2'],$post_data['Attach3'],$post_data['Attach4'],$post_data['Attach5'],$post_data['TransAmount'],$post_data['TransCurrency']) && !empty($post_data['AppInsID']) && !empty($post_data['DocID']) && !empty($post_data['QueDesc']) && !empty($post_data['TransAmount']) && !empty($post_data['TransCurrency']) && $post_data['TransAmount'] > 0))
		{
			return json_encode(array("status"=>"H02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->add_query($post_data);		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"H03","message"=>$e->getMessage()));
	}    
};

$AddComment = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		
		//START: Validate Input
		if(!(isset($post_data['AppInsID'],$post_data['QueID'],$post_data['CommDesc'],$post_data['Attach1'],$post_data['Attach2'],$post_data['Attach3'],$post_data['Attach4'],$post_data['Attach5']) && !empty($post_data['AppInsID']) && !empty($post_data['QueID']) && !empty($post_data['CommDesc'])))
		{
			return json_encode(array("status"=>"K02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->add_comment($post_data);		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"K03","message"=>$e->getMessage()));
	}    
};

$QueList = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		
		//START: Validate Input
		if(!(isset($post_data['AppInsID'],$post_data['UserType']) && !empty($post_data['AppInsID']) && !empty($post_data['UserType'])) )
		{
			return json_encode(array("status"=>"L02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->que_list($post_data);		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"L03","message"=>$e->getMessage()));
	}    
};

$CommList = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		
		//START: Validate Input
		if(!(isset($post_data['AppInsID'],$post_data['QueID']) && !empty($post_data['AppInsID']) && !empty($post_data['QueID'])))
		{
			return json_encode(array("status"=>"M02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->comm_list($post_data);		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"M03","message"=>$e->getMessage()));
	}    
};

$PreTransaction = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		
		//START: Validate Input
		if(!(isset($post_data['AppInsID'],$post_data['QueID'],$post_data['TransAmount'],$post_data['TransCurrency']) && !empty($post_data['AppInsID']) && !empty($post_data['QueID']) && !empty($post_data['TransAmount']) && !empty($post_data['TransCurrency']) && $post_data['TransAmount'] > 0))
		{
			return json_encode(array("status"=>"I02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->pre_transction($post_data);		
		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"I03","message"=>$e->getMessage()));
	}    
};

$PostTransaction = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		
		//START: Validate Input
		if(!(isset($post_data['AppInsID'],$post_data['TransID'],$post_data['TransNo'],$post_data['TransPGDet'],$post_data['TransStatus']) && !empty($post_data['AppInsID']) && !empty($post_data['TransID']) && !empty($post_data['TransNo']) && !empty($post_data['TransStatus'])))
		{
			return json_encode(array("status"=>"J02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->post_transction($post_data);		
		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"J03","message"=>$e->getMessage()));
	}    
};

$GetListOfActiveDoc = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		//START: Validate Input
		if(!(isset($post_data['AppInsID'])))
		{
			return json_encode(array("status"=>"N02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->dr_list($post_data);		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"N03","message"=>$e->getMessage()));
	}    
};

$MakeDrOnline = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		//START: Validate Input
		if(!(isset($post_data['AppInsID'],$post_data['DrAppInsID'])))
		{
			return json_encode(array("status"=>"O02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->make_dr_online($post_data);		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"O03","message"=>$e->getMessage()));
	}    
};

$GetTransactionList = function ($req, $res, $args) {
	try
	{
		$post_data = $req->getParsedBody();
		
		
		//START: Validate Input
		if(!(isset($post_data['AppInsID']) && !empty($post_data['AppInsID'])))
		{
			return json_encode(array("status"=>"P02","message"=>"Invalid Input"));			
		}
		//END: Validate Input
		
		//START: Check login
		$doc = new docdb();
		return $doc->trans_list($post_data);		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"P03","message"=>$e->getMessage()));
	}    
};

$GetCountriesList = function ($req, $res, $args) {
	try
	{
		//$post_data = $req->getParsedBody();
		//START: Check login
		$doc = new docdb();
		return $doc->countries_list();		
	}
	catch(Exception $e)
	{
		return json_encode(array("status"=>"Q03","message"=>$e->getMessage()));
	}    
};

?>