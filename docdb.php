<?php
require "config.php";
class docdb
{
	public $db;
	public $config;
	function __construct()
	{
		$this->config = $GLOBALS['config'];
		$config = $this->config;
		$this->db = new PDO('mysql:host='.$config['host'].';dbname='.$config['dbname'].';charset=utf8', $config['dbusername'], $config['dbuserpass']);
		$this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	}
	
	function is_user_not_exist($UserMob)
	{
		$sql = "SELECT count(UserID) FROM Users WHERE UserMob = '".$UserMob."'";
		if ($this->db->query($sql)->fetchColumn() > 0) 
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	function create_user($post_data)
	{
		try {  
				$age = round((time()-strtotime($post_data['UserDOB']))/(365*24*60*60));
				$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->db->beginTransaction();
				$st = $this->db->prepare("INSERT INTO Users(UserMob,UserName,UserEmail,UserGender,UserDOB,UserAge,UserPass,UserCountry,UserMedicalHistory,UserExistingDisease) VALUES (:UserMob,:UserName,:UserEmail,:UserGender,:UserDOB,:UserAge,:UserPass,:UserCountry,:UserMedicalHistory,:UserExistingDisease)");
				$st->execute(array('UserMob'=>$post_data['UserMob'],
								  'UserName'=>$post_data['UserName'],
								  'UserEmail'=>$post_data['UserEmail'],
								  'UserGender'=>$post_data['UserGender'],
								  'UserDOB'=>$post_data['UserDOB'],
								  'UserAge'=>$age,
								  'UserPass'=>md5($post_data['UserPass']),
								  'UserCountry'=>$post_data['UserCountry'] !='' ? $this->getCountryCodefromName($post_data['UserCountry']) : 'IN',
								  'UserMedicalHistory'=>$post_data['UserMedicalHistory'],
								  'UserExistingDisease'=>$post_data['UserExistingDisease']));
				$UserID = $this->db->lastInsertId();
				
				$st = $this->db->prepare("INSERT INTO MobileAppIns(UserID,AppInsDeviceID,AppInsDeviceOS,AppInsPushNtfID) VALUES (:UserID,:AppInsDeviceID,:AppInsDeviceOS,:AppInsPushNtfID)");
				$st->execute(array('UserID'=>$UserID,
									  'AppInsDeviceID'=>$post_data['DeviceID'],
									  'AppInsDeviceOS'=>$post_data['DeviceOS'],
									  'AppInsPushNtfID'=>$post_data['PushNtfID']));
									  
				$AppInsID = $this->db->lastInsertId();
				
				$this->db->commit();
				
				return json_encode(array("status"=>"A01","AppInsID"=>$AppInsID));
		
		} 
		catch (Exception $e) 
		{
		  $this->db->rollBack();
		  return json_encode(array("status"=>"A04","message"=>"Users/MobileAppIns insert error - ".$e->getMessage()));
		}		
	}
	
	function get_user_det($post_data)
	{
		try 
		{
			$checkmob = $this->db->prepare("SELECT UserID,UserPass,UserStatus,UserType FROM Users WHERE UserMob = '".$post_data['UserMob']."'");
			$checkmob->execute();
			if($checkmob->rowCount() == 0)
			{
				return json_encode(array("status"=>"B05"));
			}
			
			$result = $checkmob->fetch(PDO::FETCH_ASSOC);

			if($result['UserStatus'] == 'N')
			{
				return json_encode(array("status"=>"B07"));
			}

			if(!(md5($post_data['UserPass'])==$result['UserPass']))
			{
				return json_encode(array("status"=>"B04"));
			}
			
			$AppInsID = $post_data['AppInsID'];
			if($AppInsID == '')
			{
				if($result['UserType'] == "D")
				{
					$checkapp = $this->db->prepare("SELECT M.AppInsID FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.UserID = '".$result['UserID']."' LIMIT 0,1");
				}
				else
				{
					$checkapp = $this->db->prepare("SELECT M.AppInsID FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.UserID = '".$result['UserID']."' AND M.AppInsDeviceID = '".$post_data['DeviceID']."' AND M.AppInsDeviceOS = '".$post_data['DeviceOS']."'");
				}
				$checkapp->execute();
				if($checkapp->rowCount()>0)
				{
					$rs = $checkapp->fetch(PDO::FETCH_ASSOC);
					$AppInsID = $rs['AppInsID'];
				}
			}		
						
			if($AppInsID != '')
			{
				$checkapp = $this->db->prepare("SELECT U.UserID,U.UserMob,U.UserName,U.UserEmail,U.UserGender,U.UserDOB,U.UserAge,U.UserType,U.UserCountry,U.UserMedicalHistory,U.UserExistingDisease,U.UserQualification,U.UserProfile,U.UserDocFee,U.UserDept,U.UserRegDate,IF(U.UserLastLoginDate = '0000-00-00 00:00:00','',U.UserLastLoginDate) AS UserLastLoginDate,U.UserStatus,M.AppInsID,M.AppInsDeviceID,M.AppInsDeviceOS,M.AppInsDate,IF(M.AppInsLastLoginDate = '0000-00-00 00:00:00','',M.AppInsLastLoginDate) AS AppInsLastLoginDate,M.AppInsLastRegSyncDate,M.AppInsPushNtfID,M.AppInsStatus,UserRole,DrOnlineStatus,C.CounFee,C.CounCurrency FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) JOIN Countries AS C ON(C.CounCode = U.UserCountry) WHERE M.UserID = '".$result['UserID']."' AND M.AppInsID = '".$AppInsID."'");
				$checkapp->execute();
				if($checkapp->rowCount()==0)
				{
					return json_encode(array("status"=>"B06"));
				}
				
				$rs = $checkapp->fetch(PDO::FETCH_ASSOC);
				if($rs['AppInsStatus'] == 'N')
				{
					return json_encode(array("status"=>"B08"));
				}
				$st = $this->db->prepare("UPDATE Users SET UserLastLoginDate = NOW() WHERE UserID = '".$rs['UserID']."'");
				$st->execute();
				
				if(!empty($post_data['PushNtfID']))
				{
					$stup = $this->db->prepare("UPDATE MobileAppIns SET AppInsPushNtfID = :AppInsPushNtfID , AppInsDeviceID = :AppInsDeviceID ,AppInsDeviceOS = :AppInsDeviceOS , AppInsLastLoginDate = NOW() WHERE AppInsID = :AppInsID");
				    $stup->execute(array('AppInsID'=>$AppInsID,
										'AppInsDeviceID'=>$post_data['DeviceID'],
										'AppInsDeviceOS'=>$post_data['DeviceOS'],
							           'AppInsPushNtfID'=>$post_data['PushNtfID']));
				}
				else{
					$stup = $this->db->prepare("UPDATE MobileAppIns SET AppInsLastLoginDate = NOW(), AppInsDeviceID = :AppInsDeviceID ,AppInsDeviceOS = :AppInsDeviceOS  WHERE AppInsID = :AppInsID");
				    $stup->execute(array('AppInsID'=>$AppInsID,
										'AppInsDeviceID'=>$post_data['DeviceID'],
										'AppInsDeviceOS'=>$post_data['DeviceOS'],
										));
				}
				
				return json_encode(array("status"=>"B01","userinfo"=>$rs));
			}
			else
			{
				$st = $this->db->prepare("INSERT INTO MobileAppIns(UserID,AppInsDeviceID,AppInsDeviceOS,AppInsPushNtfID) VALUES (:UserID,:AppInsDeviceID,:AppInsDeviceOS,:AppInsPushNtfID)");
				$st->execute(array('UserID'=>$result['UserID'],
							  'AppInsDeviceID'=>$post_data['DeviceID'],
							  'AppInsDeviceOS'=>$post_data['DeviceOS'],
							  'AppInsPushNtfID'=>$post_data['PushNtfID']));
							  
				$AppInsID = $this->db->lastInsertId();
		
				$checkapp = $this->db->prepare("SELECT U.UserID,U.UserMob,U.UserName,U.UserEmail,U.UserGender,U.UserDOB,U.UserAge,U.UserType,U.UserCountry,U.UserMedicalHistory,U.UserExistingDisease,U.UserQualification,U.UserProfile,U.UserDocFee,U.UserDept,U.UserRegDate,IF(U.UserLastLoginDate = '0000-00-00 00:00:00','',U.UserLastLoginDate) AS UserLastLoginDate,U.UserStatus,M.AppInsID,M.AppInsDeviceID,M.AppInsDeviceOS,M.AppInsDate,IF(M.AppInsLastLoginDate = '0000-00-00 00:00:00','',M.AppInsLastLoginDate) AS AppInsLastLoginDate,M.AppInsLastRegSyncDate,M.AppInsPushNtfID,M.AppInsStatus,UserRole,DrOnlineStatus,C.CounFee,C.CounCurrency FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) JOIN Countries AS C ON(C.CounCode = U.UserCountry) WHERE M.AppInsID = '".$AppInsID."'");
				$checkapp->execute();
				$rs = $checkapp->fetch(PDO::FETCH_ASSOC);
				
				$st = $this->db->prepare("UPDATE Users SET UserLastLoginDate = NOW() WHERE UserID = '".$rs['UserID']."'");
				$st->execute();
				return json_encode(array("status"=>"B01","userinfo"=>$rs));
			}	
			
		} 
		catch (Exception $e) 
		{
		  return json_encode(array("status"=>"B03","message"=>$e->getMessage()));
		}
	}
	
	function forget_pass($post_data)
	{
		try 
		{
			$checkapp = $this->db->prepare("SELECT * FROM Users WHERE UserMob = '".$post_data['UserMob']."'");
			$checkapp->execute();
			if($checkapp->rowCount()==0)
			{
				return json_encode(array("status"=>"C04"));
			}
			$rs = $checkapp->fetch(PDO::FETCH_ASSOC);
			
			$temp_pass = $this->randomPassword();
			$settemppass = $this->db->prepare("UPDATE Users SET UserPass = md5('".$temp_pass."') WHERE UserMob = '".$post_data['UserMob']."'");
			$settemppass->execute();
			
			$message = "<p>Dear ".$rs['UserName'].",</p><p>Your new password is <b>".$temp_pass."</b></p><p>Regards<br>".$this->config['AppTitle']."</p>";
			
			$headers = "MIME-Version: 1.0" . "\r\n".
					   "Content-type:text/html;charset=UTF-8" . "\r\n".
					   'From: ' .$this->config['fromEmail']. "\r\n" .
					   'Reply-To: ' .$this->config['fromEmail']. "\r\n";
			
			mail($rs['UserEmail'], 'Forgot Password', $message,$headers);
						
			return json_encode(array("status"=>"C01","Email"=>$rs['UserEmail']));
		} 
		catch (Exception $e) 
		{
		  return json_encode(array("status"=>"C03","message"=>$e->getMessage()));
		}
	}
	
	function randomPassword() 
	{
		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < 8; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass); //turn the array into a string
	}
	
	function user_update($post_data)
	{
		try 
		{
			$checkapp = $this->db->prepare("SELECT * FROM Users WHERE UserMob = '".$post_data['UserMob']."' AND UserID != (SELECT UserID FROM MobileAppIns WHERE AppInsID = '".$post_data['AppInsID']."')");
			$checkapp->execute();
			if($checkapp->rowCount()>0)
			{
				return json_encode(array("status"=>"D03"));
			}
			
			$upqur = $this->db->prepare("UPDATE Users SET UserName = :UserName,UserGender = :UserGender,UserMob = :UserMob,UserDOB = :UserDOB,UserEmail = :UserEmail,UserMedicalHistory = :UserMedicalHistory,UserExistingDisease = :UserExistingDisease,UserQualification = :UserQualification,UserProfile = :UserProfile,UserDocFee = :UserDocFee WHERE UserID = (SELECT UserID FROM MobileAppIns WHERE AppInsID = :AppInsID LIMIT 0,1)");
			$upqur->bindValue(":UserName", $post_data['UserName']);
			$upqur->bindValue(":UserGender", $post_data['UserGender']);
			$upqur->bindValue(":UserMob", $post_data['UserMob']);
			$upqur->bindValue(":UserDOB", $post_data['UserDOB']);
			$upqur->bindValue(":UserEmail", $post_data['UserEmail']);
			$upqur->bindValue(":UserMedicalHistory", $post_data['UserMedicalHistory']);
			$upqur->bindValue(":UserExistingDisease", $post_data['UserExistingDisease']);
			$upqur->bindValue(":UserQualification", $post_data['UserQualification']);
			$upqur->bindValue(":UserProfile", $post_data['UserProfile']);
			$upqur->bindValue(":UserDocFee", $post_data['UserDocFee']);
			$upqur->bindValue(":AppInsID", $post_data['AppInsID']);
			$upqur->execute();
			
			return json_encode(array("status"=>"D01","AppInsID"=>$post_data['AppInsID']));/**/
		} 
		catch (Exception $e) 
		{
		  return json_encode(array("status"=>"D03","message"=>$e->getMessage()));
		}
	}
	
	function change_pass($post_data)
	{
		try 
		{
			$checkapp = $this->db->prepare("SELECT * FROM Users WHERE UserMob = :UserMob AND UserID = (SELECT UserID FROM MobileAppIns WHERE AppInsID = :AppInsID)");
			$checkapp->bindValue(":AppInsID", $post_data['AppInsID']);
			$checkapp->bindValue(":UserMob", $post_data['UserMob']);
			$checkapp->execute();
			if($checkapp->rowCount()==0)
			{
				return json_encode(array("status"=>"E03"));
			}
			
			$upqur = $this->db->prepare("UPDATE Users SET UserPass = :UserPass WHERE UserID = (SELECT UserID FROM MobileAppIns WHERE AppInsID = :AppInsID)");
			$upqur->bindValue(":UserPass", md5($post_data['UserPass']));
			$upqur->bindValue(":AppInsID", $post_data['AppInsID']);
			$upqur->execute();
			
			return json_encode(array("status"=>"E01","AppInsID"=>$post_data['AppInsID']));
		} 
		catch (Exception $e) 
		{
		  return json_encode(array("status"=>"E03","message"=>$e->getMessage()));
		}
	}
	
	function master_sync($post_data)
	{
		try 
		{
			$mstSync = array();
			$mob_ins_info_sql = $this->db->prepare("SELECT U.UserID,U.UserMob,U.UserName,U.UserEmail,U.UserGender,U.UserDOB,U.UserAge,U.UserType,U.UserMedicalHistory,U.UserExistingDisease,U.UserQualification,U.UserProfile,U.UserDocFee,U.UserDept,U.UserRegDate,U.UserLastLoginDate,U.UserStatus,M.AppInsID,M.AppInsDeviceID,M.AppInsDeviceOS,M.AppInsDate,M.AppInsLastLoginDate,M.AppInsLastRegSyncDate,M.AppInsPushNtfID,M.AppInsStatus FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.AppInsID = :AppInsID AND M.AppInsStatus = 'Y'");
			$mob_ins_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$mob_ins_info_sql->execute();
			if($mob_ins_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"F04"));
			}
			
			$rs = $mob_ins_info_sql->fetch(PDO::FETCH_ASSOC);
			$mstSync['UserAppDet'] = $rs;
			
			$query_info_sql = $this->db->prepare("SELECT QueID, QueDesc, QueDate, QueExpiryDate FROM Queries WHERE PatID = :AppInsID");
			$query_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$query_info_sql->execute();
			$query_rs = $query_info_sql->fetchAll(PDO::FETCH_ASSOC);
			$mstSync['QueryDet'] = $query_rs;
			
			$comm_info_sql = $this->db->prepare("SELECT * FROM QueriesComment WHERE QueID IN(SELECT QueID FROM Queries WHERE PatID = :AppInsID)");
			$comm_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$comm_info_sql->execute();
			$comm_rs = $comm_info_sql->fetchAll(PDO::FETCH_ASSOC);
			$mstSync['QueryCommDet'] = $comm_rs;
			
			$trans_info_sql = $this->db->prepare("SELECT * FROM Transaction WHERE UserID IN (SELECT UserID FROM MobileAppIns WHERE AppInsID = :AppInsID) AND QueID IN (SELECT QueID FROM Queries WHERE PatID = :AppInsID)");
			$trans_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$trans_info_sql->execute();
			$trans_rs = $trans_info_sql->fetchAll(PDO::FETCH_ASSOC);
			$mstSync['TransactionDet'] = $trans_rs;
			
			$mstSync['status'] = "F01";
			
			return json_encode($mstSync);
			
		} 
		catch (Exception $e) 
		{
		  return json_encode(array("status"=>"F03","message"=>$e->getMessage()));
		}
	}
	
	function regular_sync($post_data)
	{
		try 
		{
			$regSync = array();
			$mob_ins_info_sql = $this->db->prepare("SELECT U.UserID,U.UserMob,U.UserName,U.UserEmail,U.UserGender,U.UserDOB,U.UserAge,U.UserType,U.UserMedicalHistory,U.UserExistingDisease,U.UserQualification,U.UserProfile,U.UserDocFee,U.UserDept,U.UserRegDate,U.UserLastLoginDate,U.UserStatus,M.AppInsID,M.AppInsDeviceID,M.AppInsDeviceOS,M.AppInsDate,M.AppInsLastLoginDate,M.AppInsLastRegSyncDate,M.AppInsPushNtfID,M.AppInsStatus FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.AppInsID = :AppInsID AND M.AppInsStatus = 'Y'");
			$mob_ins_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$mob_ins_info_sql->execute();
			if($mob_ins_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"G04"));
			}
			
			$rs = $mob_ins_info_sql->fetch(PDO::FETCH_ASSOC);
			$regSync['UserAppDet'] = $rs;
			
			$query_info_sql = $this->db->prepare("SELECT QueID, QueDesc, QueDate, QueExpiryDate FROM Queries WHERE PatID = :AppInsID AND QueDate >= '".$rs['AppInsLastRegSyncDate']."'");
			$query_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$query_info_sql->execute();
			$query_rs = $query_info_sql->fetchAll(PDO::FETCH_ASSOC);
			$regSync['QueryDet'] = $query_rs;
			
			$comm_info_sql = $this->db->prepare("SELECT * FROM QueriesComment WHERE QueID IN(SELECT QueID FROM Queries WHERE PatID = :AppInsID) AND CommDate >= '".$rs['AppInsLastRegSyncDate']."'");
			$comm_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$comm_info_sql->execute();
			$comm_rs = $comm_info_sql->fetchAll(PDO::FETCH_ASSOC);
			$regSync['QueryCommDet'] = $comm_rs;
			
			$trans_info_sql = $this->db->prepare("SELECT * FROM Transaction WHERE UserID IN (SELECT UserID FROM MobileAppIns WHERE AppInsID = :AppInsID) AND QueID IN (SELECT QueID FROM Queries WHERE PatID = :AppInsID) AND TransDate >= '".$rs['AppInsLastRegSyncDate']."'");
			$trans_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$trans_info_sql->execute();
			$trans_rs = $trans_info_sql->fetchAll(PDO::FETCH_ASSOC);
			$regSync['TransactionDet'] = $trans_rs;
			
			$regSync['status'] = "G01";
			
			return json_encode($regSync);
		} 
		catch (Exception $e) 
		{
		  return json_encode(array("status"=>"G03","message"=>$e->getMessage()));
		}
	}
	
	function add_query($post_data)
	{
		try 
		{
			//$this->db->beginTransaction();
			//START: Check AppInsID Exist or not
			$ins_info_sql = $this->db->prepare("SELECT U.UserID, U.UserName FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.AppInsID = :AppInsID AND M.AppInsStatus = 'Y'");
			$ins_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$ins_info_sql->execute();
			if($ins_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"H04"));
			}
			
			$rs = $ins_info_sql->fetch(PDO::FETCH_ASSOC);
			$UserID = $rs['UserID'];
			$UserName = $rs['UserName'];
			//END: Check AppInsID Exist or not
			
			//START: Get Online Doc AppInsID
			$ins_online_sql = $this->db->prepare("SELECT M.AppInsID FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE U.DrOnlineStatus = 'ON' AND M.AppInsStatus = 'Y' ORDER BY AppInsLastLoginDate DESC LIMIT 0,1");
			$ins_online_sql->execute();
			if($ins_online_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"H05"));
			}
			$ins_online_rs = $ins_online_sql->fetch(PDO::FETCH_ASSOC);
			$post_data['DocID'] = $ins_online_rs['AppInsID'];
			//END: Get Online Doc AppInsID
			
			//START: Insert into Queries Table 
			$add_qur_sql = $this->db->prepare("INSERT INTO Queries(PatID,DocID,QueDesc,QueMedicalHistory,QueExistingDisease,QueExpiryDate) VALUES (:PatID,:DocID,:QueDesc,:QueMedicalHistory,:QueExistingDisease,NOW())");
			$add_qur_sql->bindValue(":PatID", $post_data['AppInsID']);
			$add_qur_sql->bindValue(":DocID", $post_data['DocID']);
			$add_qur_sql->bindValue(":QueDesc", $post_data['QueDesc']);
			$add_qur_sql->bindValue(":QueMedicalHistory", $post_data['QueMedicalHistory']);
			$add_qur_sql->bindValue(":QueExistingDisease", $post_data['QueExistingDisease']);			
			$add_qur_sql->execute();
			$QueID = $this->db->lastInsertId();
			//END: Insert into Queries Table
			
			//START: Upload Attachments
			$CommAttach1 = $CommAttach2 = $CommAttach3 = $CommAttach4 = $CommAttach5 = "";
						
			if(isset($post_data['Attach1']) && $post_data['Attach1']!="")
			{
				$CommAttach1 = "image_upload/".$QueID."_".rand(1,100)."_CommAttach1.jpg";
				$this->upload_image_attach($CommAttach1,$post_data['Attach1']);
			}
			if(isset($post_data['Attach2']) && $post_data['Attach2']!="")
			{
				$CommAttach2 = "image_upload/".$QueID."_".rand(1,100)."_CommAttach2.jpg";
				$this->upload_image_attach($CommAttach2,$post_data['Attach2']);
			}
			if(isset($post_data['Attach3']) && $post_data['Attach3']!="")
			{
				$CommAttach3 = "image_upload/".$QueID."_".rand(1,100)."_CommAttach3.jpg";
				$this->upload_image_attach($CommAttach3,$post_data['Attach3']);
			}
			if(isset($post_data['Attach4']) && $post_data['Attach4']!="")
			{
				$CommAttach4 = "image_upload/".$QueID."_".rand(1,100)."_CommAttach4.jpg";
				$this->upload_image_attach($CommAttach4,$post_data['Attach4']);
			}
			if(isset($post_data['Attach5']) && $post_data['Attach5']!="")
			{
				$CommAttach5 = "image_upload/".$QueID."_".rand(1,100)."_CommAttach5.jpg";
				$this->upload_image_attach($CommAttach5,$post_data['Attach5']);
			}
			//END: Upload Attachments
			
			//START: Insert INTO QueriesComment
			$add_comm_sql = $this->db->prepare("INSERT INTO QueriesComment(CommBy,QueID,CommDesc,CommAttach1,CommAttach2,CommAttach3,CommAttach4,CommAttach5) 
												VALUES (:CommBy,:QueID,:CommDesc,:CommAttach1,:CommAttach2,:CommAttach3,:CommAttach4,:CommAttach5)");
			$add_comm_sql->bindValue(":CommBy", $post_data['AppInsID']);
			$add_comm_sql->bindValue(":QueID", $QueID);
			$add_comm_sql->bindValue(":CommDesc", $post_data['QueDesc']);
			$add_comm_sql->bindValue(":CommAttach1", $CommAttach1);
			$add_comm_sql->bindValue(":CommAttach2", $CommAttach2);
			$add_comm_sql->bindValue(":CommAttach3", $CommAttach3);
			$add_comm_sql->bindValue(":CommAttach4", $CommAttach4);
			$add_comm_sql->bindValue(":CommAttach5", $CommAttach5);
			$add_comm_sql->execute();
			$CommID = $this->db->lastInsertId();
			//END: Insert INTO QueriesComment
			
			//START: Insert INTO Transaction
			$add_trans_sql = $this->db->prepare("INSERT INTO Transaction(UserID,QueID,TransAmount,TransCurrency) 
												VALUES (:UserID,:QueID,:TransAmount,:TransCurrency)");
			$add_trans_sql->bindValue(":UserID", $UserID);
			$add_trans_sql->bindValue(":QueID", $QueID);
			$add_trans_sql->bindValue(":TransAmount", $post_data['TransAmount']);
			$add_trans_sql->bindValue(":TransCurrency", $post_data['TransCurrency']);
			$add_trans_sql->execute();
			$TransID = $this->db->lastInsertId();
			//END: Insert INTO Transaction
			
			//$this->db->commit();
			
			return json_encode(array("status"=>"H01","QueID"=>$QueID,"CommID"=>$CommID,"TransID"=>$TransID));	/**/
		} 
		catch (Exception $e) 
		{
		  //$this->db->rollBack();
		  return json_encode(array("status"=>"H03","message"=>$e->getMessage()));
		}
	}
	
	function pre_transction($post_data)
	{
		try 
		{
			//$this->db->beginTransaction();
			//START: Check AppInsID Exist or not
			$ins_info_sql = $this->db->prepare("SELECT U.UserID, U.UserName FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.AppInsID = :AppInsID AND M.AppInsStatus = 'Y'");
			$ins_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$ins_info_sql->execute();
			if($ins_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"I04"));
			}
			
			$rs = $ins_info_sql->fetch(PDO::FETCH_ASSOC);
			$UserID = $rs['UserID'];
			$UserName = $rs['UserName'];
			//END: Check AppInsID Exist or not
			
			//START: Insert INTO Transaction
			$add_trans_sql = $this->db->prepare("INSERT INTO Transaction(UserID,QueID,TransAmount,TransCurrency) 
												VALUES (:UserID,:QueID,:TransAmount,:TransCurrency)");
			$add_trans_sql->bindValue(":UserID", $UserID);
			$add_trans_sql->bindValue(":QueID", $post_data['QueID']);
			$add_trans_sql->bindValue(":TransAmount", $post_data['TransAmount']);
			$add_trans_sql->bindValue(":TransCurrency", $post_data['TransCurrency']);
			$add_trans_sql->execute();
			$TransID = $this->db->lastInsertId();
			//END: Insert INTO Transaction
			
			//$this->db->commit();
			
			return json_encode(array("status"=>"I01","QueID"=>$post_data['QueID'],"TransID"=>$TransID));
		} 
		catch (Exception $e) 
		{
			 //$this->db->rollBack();
			return json_encode(array("status"=>"I03","message"=>$e->getMessage()));
		}
	}
	
	function post_transction($post_data)
	{
		try 
		{
			//$this->db->beginTransaction();
			//START: Check AppInsID Exist or not
			$ins_info_sql = $this->db->prepare("SELECT U.UserID, U.UserName FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.AppInsID = :AppInsID AND M.AppInsStatus = 'Y'");
			$ins_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$ins_info_sql->execute();
			if($ins_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"J04"));
			}
			
			$rs = $ins_info_sql->fetch(PDO::FETCH_ASSOC);
			$UserID = $rs['UserID'];
			$UserName = $rs['UserName'];
			//END: Check AppInsID Exist or not
			
			//START: Check TransID Exist or not
			$ins_info_sql = $this->db->prepare("SELECT T.QueID,Q.QueDesc,Q.DocID FROM Transaction as T JOIN Queries as Q ON(Q.QueID = T.QueID) WHERE TransID = :TransID");
			$ins_info_sql->bindValue(":TransID", $post_data['TransID']);
			$ins_info_sql->execute();
			if($ins_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"J05"));
			}
			
			$rs = $ins_info_sql->fetch(PDO::FETCH_ASSOC);
			$QueID = $rs['QueID'];
			$QueDesc = $rs['QueDesc'];
			$DocID = $rs['DocID'];
			//END: Check TransID Exist or not
			
			//START: Update INTO Transaction
			$add_trans_sql = $this->db->prepare("UPDATE Transaction SET TransNo = :TransNo,TransPGDet = :TransPGDet,TransDate=NOW(),TransStatus = :TransStatus WHERE TransID = :TransID AND UserID = :UserID");
			$add_trans_sql->bindValue(":TransNo", $post_data['TransNo']);
			$add_trans_sql->bindValue(":TransPGDet", $post_data['TransPGDet']);
			$add_trans_sql->bindValue(":TransStatus", $post_data['TransStatus']);
			$add_trans_sql->bindValue(":TransID", $post_data['TransID']);
			$add_trans_sql->bindValue(":UserID", $UserID);
			$add_trans_sql->execute();
			//END: Update INTO Transaction
			
			if($post_data['TransStatus'] == "Approved")
			{
				//START: Update INTO Queries
				$add_trans_sql = $this->db->prepare("UPDATE Queries SET QueExpiryDate = NOW() + INTERVAL ".PAYMENT_VALIDITY." DAY , QueStatus = 'Y' WHERE QueID = :QueID");
				$add_trans_sql->bindValue(":QueID", $QueID);
				$add_trans_sql->execute();
				//END: Update INTO Queries
				
				//START: SEND notification    
				$msg = array
				(
					'message' 	=> $QueDesc,
					'title'		=> $UserName.' Asked',
					'subtitle'	=> $QueID,
					'tickerText'	=> '',
					'vibrate'	=> 1,
					'sound'		=> 1,
					'largeIcon'	=> 'large_icon',
					'smallIcon'	=> 'small_icon'
				);
				$this->gcm_push_notification($DocID,$msg);
				//END: SEND notification
				
				//START: Send Email
				$this->newQueryEmail($DocID,$msg);
				//END: Send Email
			}
			
			//$this->db->commit();
			
			return json_encode(array("status"=>"J01","QueID"=>$QueID,"TransID"=>$post_data['TransID']));
		} 
		catch (Exception $e) 
		{
			//$this->db->rollBack();
			return json_encode(array("status"=>"J03","message"=>$e->getMessage()));
		}
	}
	
	
	
	function add_comment($post_data)
	{
		try 
		{
			//START: Check AppInsID Exist or not
			$ins_info_sql = $this->db->prepare("SELECT U.UserID, U.UserName,U.DrOnlineStatus,U.UserType FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.AppInsID = :AppInsID AND M.AppInsStatus = 'Y'");
			$ins_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$ins_info_sql->execute();
			if($ins_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"K04"));
			}
			
			$rs = $ins_info_sql->fetch(PDO::FETCH_ASSOC);
			
			$UserID = $rs['UserID'];
			$UserName = $rs['UserName'];

			if($rs['UserType'] == 'D' && $rs['DrOnlineStatus'] != "ON")
			{
				return json_encode(array("status"=>"K06"));
			}
			//END: Check AppInsID Exist or not
			
			$comm_info_sql = $this->db->prepare("SELECT QueID,PatID,DocID FROM Queries WHERE QueID = :QueID AND (PatID = :AppInsID OR DocID IN(SELECT `AppInsID` FROM `MobileAppIns` AS MA JOIN Users AS U ON(MA.UserID = U.UserID) WHERE U.UserType = 'D' AND U.UserStatus = 'Y'))");
			$comm_info_sql->bindValue(":QueID", $post_data['QueID']);
			$comm_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$comm_info_sql->execute();
			if($comm_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"K05"));
			}
			$comm_info_rs = $comm_info_sql->fetch(PDO::FETCH_ASSOC);
			
			$QueID = $post_data['QueID'];
			
			$CommAttach1 = "";
			$CommAttach2 = "";
			$CommAttach3 = "";
			$CommAttach4 = "";
			$CommAttach5 = "";
			
			if(isset($post_data['Attach1']) && $post_data['Attach1']!="")
			{
				$CommAttach1 = "image_upload/".$QueID."_".rand(1,100)."_CommAttach1.jpg";
				$this->upload_image_attach($CommAttach1,$post_data['Attach1']);
			}
			if(isset($post_data['Attach2']) && $post_data['Attach2']!="")
			{
				$CommAttach2 = "image_upload/".$QueID."_".rand(1,100)."_CommAttach2.jpg";
				$this->upload_image_attach($CommAttach2,$post_data['Attach2']);
			}
			if(isset($post_data['Attach3']) && $post_data['Attach3']!="")
			{
				$CommAttach3 = "image_upload/".$QueID."_".rand(1,100)."_CommAttach3.jpg";
				$this->upload_image_attach($CommAttach3,$post_data['Attach3']);
			}
			if(isset($post_data['Attach4']) && $post_data['Attach4']!="")
			{
				$CommAttach4 = "image_upload/".$QueID."_".rand(1,100)."_CommAttach4.jpg";
				$this->upload_image_attach($CommAttach4,$post_data['Attach4']);
			}
			if(isset($post_data['Attach5']) && $post_data['Attach5']!="")
			{
				$CommAttach5 = "image_upload/".$QueID."_".rand(1,100)."_CommAttach5.jpg";
				$this->upload_image_attach($CommAttach5,$post_data['Attach5']);
			}
			
			$add_comm_sql = $this->db->prepare("INSERT INTO QueriesComment(CommBy,QueID,CommDesc,CommAttach1,CommAttach2,CommAttach3,CommAttach4,CommAttach5) 
												VALUES (:CommBy,:QueID,:CommDesc,:CommAttach1,:CommAttach2,:CommAttach3,:CommAttach4,:CommAttach5)");
			$add_comm_sql->bindValue(":CommBy", $post_data['AppInsID']);
			$add_comm_sql->bindValue(":QueID", $QueID);
			$add_comm_sql->bindValue(":CommDesc", $post_data['CommDesc']);
			$add_comm_sql->bindValue(":CommAttach1", $CommAttach1);
			$add_comm_sql->bindValue(":CommAttach2", $CommAttach2);
			$add_comm_sql->bindValue(":CommAttach3", $CommAttach3);
			$add_comm_sql->bindValue(":CommAttach4", $CommAttach4);
			$add_comm_sql->bindValue(":CommAttach5", $CommAttach5);
			$add_comm_sql->execute();
			$CommID = $this->db->lastInsertId();
			
			if($comm_info_rs['PatID'] == $post_data['AppInsID'])
			{
				//START: Get Online Doc AppInsID
				$ins_online_sql = $this->db->prepare("SELECT M.AppInsID FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE U.DrOnlineStatus = 'ON' AND M.AppInsStatus = 'Y' ORDER BY AppInsLastLoginDate DESC LIMIT 0,1");
				$ins_online_sql->execute();
				$ins_online_rs = $ins_online_sql->fetch(PDO::FETCH_ASSOC);
				$DocIDOnline = isset($ins_online_rs['AppInsID'])? $ins_online_rs['AppInsID'] : $comm_info_rs['DocID'];
				//END: Get Online Doc AppInsID
				//$AppInsIDn = $comm_info_rs['DocID'];
				$AppInsIDn = $DocIDOnline;
			}
			else
			{
				$AppInsIDn = $comm_info_rs['PatID'];
			}
			$msg = array
			(
				'message' 	=> $post_data['CommDesc'],
				'title'		=> $UserName.' Replied',
				'subtitle'	=> $post_data['QueID'],
				'tickerText'	=> '',
				'vibrate'	=> 1,
				'sound'		=> 1,
				'largeIcon'	=> 'large_icon',
				'smallIcon'	=> 'small_icon'
			);
			$this->gcm_push_notification($AppInsIDn,$msg);
			
			//START: send email
			$this->newCommentEmail($AppInsIDn,$msg);
			//END: send email
			
			return json_encode(array("status"=>"K01","CommID"=>$CommID));	
		} 
		catch (Exception $e) 
		{
		  return json_encode(array("status"=>"K03","message"=>$e->getMessage()));
		}
	}
	
	function que_list($post_data)
	{
		try 
		{
			$regSync = array();
			$mob_ins_info_sql = $this->db->prepare("SELECT U.UserID,U.UserMob,U.UserName,U.UserEmail,U.UserGender,U.UserDOB,U.UserAge,U.UserType,U.UserMedicalHistory,U.UserExistingDisease,U.UserQualification,U.UserProfile,U.UserDocFee,U.UserDept,U.UserRegDate,U.UserLastLoginDate,U.UserStatus,M.AppInsID,M.AppInsDeviceID,M.AppInsDeviceOS,M.AppInsDate,M.AppInsLastLoginDate,M.AppInsLastRegSyncDate,M.AppInsPushNtfID,M.AppInsStatus FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.AppInsID = :AppInsID AND M.AppInsStatus = 'Y'");
			$mob_ins_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$mob_ins_info_sql->execute();
			if($mob_ins_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"L04"));
			}
			
			//START: filter
			$to_date = @$post_data['ToDate'];
			$from_date = @$post_data['FromDate'];
			$top = @$post_data['Top'];
			$sub_sql = "";
			if($to_date != "")
			{
				$sub_sql .= " AND DATE(QueDate) <= '".$to_date."'";
			}
			
			if($from_date != "")
			{
				$sub_sql .= " AND DATE(QueDate) >= '".$from_date."'";
			}
			
			$limit = "";
			if($top !="" && $top>0)
			{
				$limit .= " LIMIT 0,".$top;
			}
			
			//END: filter
			
			if($post_data['UserType'] == 'D')
			{
				$query_info_sql = $this->db->prepare("SELECT Q.QueID, Q.QueDesc, DATE_FORMAT(Q.QueDate,'%d %b %Y %h:%i %p') as QueDate, DATE_FORMAT(QueExpiryDate,'%d %b %Y %h:%i %p') as QueExpiryDate, UP.UserName AS PatientName, UD.UserName AS DoctorName, IF(DATE(NOW()) <= DATE(QueExpiryDate), 'O','E') AS QueStatus, IF((SELECT count(CommID) FROM QueriesComment WHERE QueID = Q.QueID AND CommBy != :AppInsID AND CommReadStatus = 'U') , 'U','R') AS QueReadStatus , (SELECT count(TransID) FROM Transaction WHERE QueID = Q.QueID AND TransStatus = 'Approved') as PayCount FROM Queries AS Q JOIN MobileAppIns AS MP ON(Q.PatID = MP.AppInsID) JOIN Users AS UP ON(UP.UserID = MP.UserID) JOIN MobileAppIns AS MD ON(Q.DocID = MD.AppInsID) JOIN Users AS UD ON(UD.UserID = MD.UserID) WHERE QueStatus = 'Y' ".$sub_sql." Order by QueID desc".$limit);
			}
			else 
		    {
				$query_info_sql = $this->db->prepare("SELECT Q.QueID, Q.QueDesc, DATE_FORMAT(Q.QueDate,'%d %b %Y %h:%i %p') as QueDate, DATE_FORMAT(QueExpiryDate,'%d %b %Y %h:%i %p') as QueExpiryDate, UP.UserName AS PatientName, UD.UserName AS DoctorName, IF(DATE(NOW()) <= DATE(QueExpiryDate), 'O','E') AS QueStatus, IF((SELECT count(CommID) FROM QueriesComment WHERE QueID = Q.QueID AND CommBy != :AppInsID AND CommReadStatus = 'U') , 'U','R') AS QueReadStatus , (SELECT count(TransID) FROM Transaction WHERE QueID = Q.QueID AND TransStatus = 'Approved') as PayCount FROM Queries AS Q JOIN MobileAppIns AS MP ON(Q.PatID = MP.AppInsID) JOIN Users AS UP ON(UP.UserID = MP.UserID) JOIN MobileAppIns AS MD ON(Q.DocID = MD.AppInsID) JOIN Users AS UD ON(UD.UserID = MD.UserID) WHERE QueStatus = 'Y' AND PatID IN(SELECT AppInsID FROM MobileAppIns WHERE UserID = (SELECT UserID FROM MobileAppIns WHERE AppInsID = :AppInsID)) ".$sub_sql." Order by QueID desc".$limit);
			}
			
			$query_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$query_info_sql->execute();
			$query_rs = $query_info_sql->fetchAll(PDO::FETCH_ASSOC);
			$regSync['QueryDet'] = $query_rs;
			
			$regSync['status'] = "L01";
			
			return json_encode($regSync);
		} 
		catch (Exception $e) 
		{
		  return json_encode(array("status"=>"L03","message"=>$e->getMessage()));
		}
	}
	
	function comm_list($post_data)
	{
		try 
		{
			$regSync = array();
			$mob_ins_info_sql = $this->db->prepare("SELECT U.UserID,U.UserMob,U.UserName,U.UserEmail,U.UserGender,U.UserDOB,U.UserAge,U.UserType,U.UserMedicalHistory,U.UserExistingDisease,U.UserQualification,U.UserProfile,U.UserDocFee,U.UserDept,U.UserRegDate,U.UserLastLoginDate,U.UserStatus,M.AppInsID,M.AppInsDeviceID,M.AppInsDeviceOS,M.AppInsDate,M.AppInsLastLoginDate,M.AppInsLastRegSyncDate,M.AppInsPushNtfID,M.AppInsStatus FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.AppInsID = :AppInsID AND M.AppInsStatus = 'Y'");
			$mob_ins_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$mob_ins_info_sql->execute();
			if($mob_ins_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"M04"));
			}
			
			//START: Date filter
			$to_date = @$post_data['ToDate'];
			$from_date = @$post_data['FromDate'];
			$top = @$post_data['Top'];
			$CommID = @$post_data['CommID'];
			$sub_sql = "";
			if($to_date != "")
			{
				$sub_sql .= " AND DATE(CommDate) <= '".$to_date."'";
			}
			
			if($from_date != "")
			{
				$sub_sql .= " AND DATE(CommDate) >= '".$from_date."'";
			}
			
			if($CommID!="" && $CommID > 0)
			{
				$sub_sql .= " AND CommID = '".$CommID."'";
			}
			
			$limit = "";
			if($top !="" && $top>0)
			{
				$limit = " LIMIT 0,".$top;
			}
			//END: Date filter
			
			//$comm_info_sql = $this->db->prepare("SELECT Q.QueID, Q.QueDesc, DATE_FORMAT(Q.QueDate,'%d %b %Y') as QueDate, DATE_FORMAT(QueExpiryDate,'%d %b %Y') as QueExpiryDate, UP.UserName AS PatientName, UD.UserName AS DoctorName FROM Queries AS Q JOIN MobileAppIns AS MP ON(Q.PatID = MP.AppInsID) JOIN Users AS UP ON(UP.UserID = MP.UserID) JOIN MobileAppIns AS MD ON(Q.DocID = MD.AppInsID) JOIN Users AS UD ON(UD.UserID = MD.UserID) WHERE QueID = :QueID AND (PatID = :AppInsID OR DocID = :AppInsID)");
			$comm_info_sql = $this->db->prepare("SELECT Q.QueID, Q.QueDesc, DATE_FORMAT(Q.QueDate,'%d %b %Y %h:%i %p') as QueDate, DATE_FORMAT(QueExpiryDate,'%d %b %Y %h:%i %p') as QueExpiryDate, UP.UserName AS PatientName, UD.UserName AS DoctorName FROM Queries AS Q JOIN MobileAppIns AS MP ON(Q.PatID = MP.AppInsID) JOIN Users AS UP ON(UP.UserID = MP.UserID) JOIN MobileAppIns AS MD ON(Q.DocID = MD.AppInsID) JOIN Users AS UD ON(UD.UserID = MD.UserID) WHERE QueID = :QueID");
			$comm_info_sql->bindValue(":QueID", $post_data['QueID']);
			//$comm_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$comm_info_sql->execute();
			if($comm_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"M05"));
			}
			$query_data = $comm_info_sql->fetchAll(PDO::FETCH_ASSOC);
			
			$query_info_sql = $this->db->prepare("SELECT QC.CommID, QC.CommBy, QC.QueID, QC.CommDesc, QC.CommAttach1,QC.CommAttach2,QC.CommAttach3,QC.CommAttach4,QC.CommAttach5,DATE_FORMAT(QC.CommDate,'%d %b %Y %h:%i %p') as CommDate,QC.CommStatus,U.UserName AS CommByName, IF(QC.CommAttach1 !='' OR QC.CommAttach2 !='' OR QC.CommAttach3 !='' OR QC.CommAttach4 !='' OR QC.CommAttach5!='' , 'Y','N') AS Attachment,QC.CommReadStatus FROM QueriesComment as QC JOIN MobileAppIns AS M ON(QC.CommBy = M.AppInsID) JOIN Users AS U ON(U.UserID = M.UserID) WHERE QueID = :QueID ".$sub_sql." ORDER BY CommID desc".$limit);
			$query_info_sql->bindValue(":QueID", $post_data['QueID']);
			$query_info_sql->execute();
			$query_rs = $query_info_sql->fetchAll(PDO::FETCH_ASSOC);
			$regSync['CommDet'] = $query_rs;
			$regSync['QueryDet'] = $query_data;
			$regSync['status'] = "M01";
			
			$stread = $this->db->prepare("UPDATE QueriesComment SET CommReadStatus = 'R' WHERE CommBy != :AppInsID AND QueID = :QueID ".$sub_sql);
			$stread->bindValue(":AppInsID", $post_data['AppInsID']);
			$stread->bindValue(":QueID", $post_data['QueID']);
			$stread->execute();
			return json_encode($regSync);
		} 
		catch (Exception $e) 
		{
		  return json_encode(array("status"=>"M03","message"=>$e->getMessage()));
		}
	}
	
	function make_log()
	{
		try 
		{  
			$post = file_get_contents("php://input");
			$post_arr = json_decode($post);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$st = $this->db->prepare("INSERT INTO APILog(AppInsID,LogAPI,LogAPIRequestData) VALUES (:AppInsID,:LogAPI,:LogAPIRequestData)");
			$AppInsID = isset($post_arr->AppInsID)? $post_arr->AppInsID : "";
			$st->bindValue(":AppInsID", $AppInsID);
			$st->bindValue(":LogAPI", $_SERVER['REQUEST_URI']);
			$st->bindValue(":LogAPIRequestData", serialize($post));
			$st->execute();
		}
		catch (Exception $e) 
		{
		  
		}
	}
	
	function upload_image_attach($attach_path,$attach_val)
	{
		header("Content-Type: bitmap; charset=utf-8");
		$binary_attach = base64_decode($attach_val);
		file_put_contents($attach_path,$binary_attach);
	}	
	
	function gcm_push_notification($AppInsIDs,$msg)
	{
		
		$pushgcm_info_sql = $this->db->prepare("SELECT AppInsPushNtfID FROM MobileAppIns WHERE AppInsStatus = 'Y' AND AppInsPushNtfID !='' AND AppInsID IN(:AppInsID)");
		$pushgcm_info_sql->bindValue(":AppInsID", $AppInsIDs);
		$pushgcm_info_sql->execute();
		$pushgcm_rs = $pushgcm_info_sql->fetchAll(PDO::FETCH_ASSOC);
		$registrationIds = array();
		if(is_array($pushgcm_rs) && !empty($pushgcm_rs))
		{
			foreach($pushgcm_rs as $row)
			{
				$registrationIds[] = $row['AppInsPushNtfID'];
			}
			
			$fields = array
			(
				'registration_ids' 	=> $registrationIds,
				'data'			=> $msg
			);

			$headers = array
			(
				'Authorization: key=' . API_ACCESS_KEY,
				'Content-Type: application/json'
			);

			$ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
			$result = curl_exec($ch );
			curl_close( $ch );
			return $result;
		}
		return FALSE;		
	}
	
	function dr_list($post_data)
	{
		try 
		{
			$mob_ins_info_sql = $this->db->prepare("SELECT U.UserID FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.AppInsID = :AppInsID AND M.AppInsStatus = 'Y' AND U.UserRole = 'A'");
			$mob_ins_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$mob_ins_info_sql->execute();
			if($mob_ins_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"N04"));
			}
					
			$DrList = array();
			$query_info_sql = $this->db->prepare("SELECT M.AppInsID, U.`UserName`,U.`UserEmail`,U.DrOnlineStatus FROM `Users` AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE U.`UserType` = 'D' AND U.`UserStatus` = 'Y' AND M.`AppInsStatus` = 'Y' GROUP BY U.UserID");
			$query_info_sql->execute();
			$DrList['DrList'] = $query_info_sql->fetchAll(PDO::FETCH_ASSOC);
			$DrList['status'] = "N01";
			return json_encode($DrList);
		} 
		catch (Exception $e) 
		{
		  return json_encode(array("status"=>"N03","message"=>$e->getMessage()));
		}
	}	
	
	function make_dr_online($post_data)
	{
		try 
		{
			$mob_ins_info_sql = $this->db->prepare("SELECT U.UserID FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.AppInsID = :AppInsID AND M.AppInsStatus = 'Y'");
			$mob_ins_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$mob_ins_info_sql->execute();
			if($mob_ins_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"O04"));
			}
			
			$mob_ins_info_sql = $this->db->prepare("SELECT U.UserID FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.AppInsID = :AppInsID AND M.AppInsStatus = 'Y'");
			$mob_ins_info_sql->bindValue(":AppInsID", $post_data['DrAppInsID']);
			$mob_ins_info_sql->execute();
			if($mob_ins_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"O04"));
			}
			$dr_rs = $mob_ins_info_sql->fetch(PDO::FETCH_ASSOC);
					
			$query_info_sql = $this->db->prepare("UPDATE `Users` SET DrOnlineStatus = 'ON' WHERE `UserType` = 'D' AND `UserStatus` = 'Y' AND UserID = :UserID");
			$query_info_sql->bindValue(":UserID", $dr_rs['UserID']);
			$query_info_sql->execute();
						
			$query_info_sql = $this->db->prepare("UPDATE `Users` SET DrOnlineStatus = 'OFF' WHERE UserID != :UserID");
			$query_info_sql->bindValue(":UserID", $dr_rs['UserID']);
			$query_info_sql->execute();

			$DrOnile = array();
			$DrOnile['status'] = "O01";
			return json_encode($DrOnile);
		} 
		catch (Exception $e) 
		{
		  return json_encode(array("status"=>"O03","message"=>$e->getMessage()));
		}
	}	
	
	function trans_list($post_data)
	{
		try 
		{
			$transRs = array();
			$mob_ins_info_sql = $this->db->prepare("SELECT U.UserID,U.UserMob,U.UserName,U.UserEmail,U.UserGender,U.UserDOB,U.UserAge,U.UserType,U.UserMedicalHistory,U.UserExistingDisease,U.UserQualification,U.UserProfile,U.UserDocFee,U.UserDept,U.UserRegDate,U.UserLastLoginDate,U.UserStatus,M.AppInsID,M.AppInsDeviceID,M.AppInsDeviceOS,M.AppInsDate,M.AppInsLastLoginDate,M.AppInsLastRegSyncDate,M.AppInsPushNtfID,M.AppInsStatus FROM Users AS U JOIN MobileAppIns AS M ON(U.UserID = M.UserID) WHERE M.AppInsID = :AppInsID AND M.AppInsStatus = 'Y'");
			$mob_ins_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			$mob_ins_info_sql->execute();
			if($mob_ins_info_sql->rowCount()==0)
			{
				return json_encode(array("status"=>"P04"));
			}
			
			//START: Date filter
			$to_date = @$post_data['ToDate'];
			$from_date = @$post_data['FromDate'];
			$top = @$post_data['Top'];
			$TransID = @$post_data['TransID'];
			$UserType = @$post_data['UserType'];
			
			$sub_sql = "";
			if($to_date != "")
			{
				$sub_sql .= " AND DATE(TransDate) <= '".$to_date."'";
			}
			
			if($from_date != "")
			{
				$sub_sql .= " AND DATE(TransDate) >= '".$from_date."'";
			}
			
			if($TransID!="" && $TransID > 0)
			{
				$sub_sql .= " AND TransID = '".$TransID."'";
			}
			
			$limit = "";
			if($top !="" && $top>0)
			{
				$limit = " LIMIT 0,".$top;
			}
			//END: Date filter
			if($UserType == 'D')
			{
				$trans_info_sql = $this->db->prepare("SELECT TransID,t.UserID,u.UserName,QueID,TransAmount,TransCurrency,TransNo,TransPGDet,DATE_FORMAT(TransDate,'%d %b %Y %h:%i %p') as TransDate, TransStatus FROM Transaction AS t JOIN Users as u on(u.UserID = t.UserID) WHERE TransStatus != 'Pending' ".$sub_sql." ORDER BY TransID desc".$limit);
			}
			else
			{
				$trans_info_sql = $this->db->prepare("SELECT TransID,t.UserID,u.UserName,QueID,TransAmount,TransCurrency,TransNo,TransPGDet,DATE_FORMAT(TransDate,'%d %b %Y %h:%i %p') as TransDate, TransStatus FROM Transaction AS t JOIN Users as u on(u.UserID = t.UserID) WHERE TransStatus != 'Pending' AND t.UserID IN(SELECT UserID FROM MobileAppIns WHERE AppInsID = :AppInsID) ".$sub_sql." ORDER BY TransID desc".$limit);
				$trans_info_sql->bindValue(":AppInsID", $post_data['AppInsID']);
			}
			
			$trans_info_sql->execute();
			
			$trans_rs = $trans_info_sql->fetchAll(PDO::FETCH_ASSOC);
			$transRs['TransDet'] = $trans_rs;
			$transRs['status'] = "P01";
			
			return json_encode($transRs);
		} 
		catch (Exception $e) 
		{
		  return json_encode(array("status"=>"P03","message"=>$e->getMessage()));
		}
	}
	
	function countries_list()
	{
		try 
		{
			$counrs = array();
			$country_sql = $this->db->prepare("SELECT CounName FROM Countries WHERE CounStatus = 'Y'");
			$country_sql->execute();
			$countries_rs = $country_sql->fetchAll(PDO::FETCH_ASSOC);
						
			$counrs['Countries'] = $countries_rs;
			$counrs['status'] = "Q01";
			
			return json_encode($counrs);
		} 
		catch (Exception $e) 
		{
		  return json_encode(array("status"=>"Q03","message"=>$e->getMessage()));
		}
	}
	
	private function getCountryCodefromName($CounName)
	{
		if(empty($CounName))
		{
			return "";
		}
		
		$country_sql = $this->db->prepare("SELECT CounCode FROM Countries WHERE CounName = :CounName");
		$country_sql->bindValue(":CounName", $CounName);
		$country_sql->execute();
		$country_rs = $country_sql->fetch(PDO::FETCH_ASSOC);
		if(isset($country_rs['CounCode']) && !empty($country_rs['CounCode']))
		{
			return $country_rs['CounCode'];
		}
		else
		{
			return "";
		}
	}
	
	private function newQueryEmail($AppID,$msg)
	{
		$sql = "SELECT `UserName`,`UserEmail`,`UserType` FROM `Users` AS U JOIN MobileAppIns AS MA ON (MA.`UserID` = U.`UserID`) WHERE AppInsID = :AppInsID";
		$mem_sql = $this->db->prepare($sql);
		$mem_sql->bindValue(":AppInsID",$AppID);
		$mem_sql->execute();
		$mem_rs = $mem_sql->fetch(PDO::FETCH_ASSOC);
		
		if(isset($mem_rs['UserEmail']) && !empty($mem_rs['UserEmail']))
		{
			$message = "<p>Dear ".$mem_rs['UserName'].",</p><p><b>New Query (".$msg['title']."):</b><br/>".$msg['message']."</b></p><p>Regards<br>".$this->config['AppTitle']."</p>";
			$subject = "New Query (".$this->config['AppTitle'].")";			
			return $this->sendEmail($mem_rs['UserEmail'],$subject,$message);			
		}
		else
		{
			return FALSE;
		}				
	}
	
	private function newCommentEmail($AppID,$msg)
	{
		$sql = "SELECT `UserName`,`UserEmail`,`UserType` FROM `Users` AS U JOIN MobileAppIns AS MA ON (MA.`UserID` = U.`UserID`) WHERE AppInsID = :AppInsID";
		$mem_sql = $this->db->prepare($sql);
		$mem_sql->bindValue(":AppInsID",$AppID);
		$mem_sql->execute();
		$mem_rs = $mem_sql->fetch(PDO::FETCH_ASSOC);
		
		if(isset($mem_rs['UserEmail']) && !empty($mem_rs['UserEmail']))
		{
			$message = "<p>Dear ".$mem_rs['UserName'].",</p><p><b>".$msg['title'].":</b><br/>".$msg['message']."</b></p><p>Regards<br>".$this->config['AppTitle']."</p>";
			$subject = "New Comment (".$this->config['AppTitle'].")";			
			return $this->sendEmail($mem_rs['UserEmail'],$subject,$message);			
		}
		else
		{
			return FALSE;
		}				
	}
	
	private function sendEmail($to,$sub,$msg)
	{
		$headers = "MIME-Version: 1.0" . "\r\n".
			   "Content-type:text/html;charset=UTF-8" . "\r\n".
			   'Bcc: ' .$this->config['BccEmail']. "\r\n".
			   'From: ' .$this->config['fromEmail']. "\r\n" .
			   'Reply-To: ' .$this->config['fromEmail']. "\r\n";
		return mail($to,$sub,$msg,$headers);
	}
}
?>