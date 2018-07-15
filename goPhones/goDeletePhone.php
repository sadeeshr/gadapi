<?php
/**
 * @file 		goDeletePhone.php
 * @brief 		API to delete specific Phone 
 * @copyright 	Copyright (c) 2018 GOautodial Inc.
 * @author		Demian Lizandro A. Biscocho
 * @author     	Alexander Jim H. Abenoja
 *
 * @par <b>License</b>:
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
**/
    
    include_once("goAPI.php");
    
	$log_user 										= $session_user;
	$log_group 										= go_get_groupid($session_user, $astDB);
	$log_ip 										= $astDB->escape($_REQUEST['log_ip']);	
	
    // POST or GET Variables
    $extensions 									= $_REQUEST['extension'];
	$action 										= $astDB->escape($_REQUEST['action']);
    
    // Error Checking
	if (empty($log_user) || is_null($log_user)) {
		$apiresults 								= array(
			"result" 									=> "Error: Session User Not Defined."
		);
	} elseif (empty($extensions) || is_null($extensions)) { 
		$apiresults									= array(
			"result" 									=> "Error: Set a value for EXTEN ID."
		); 
	} else {
		if (checkIfTenant($log_group, $goDB)) {
			$astDB->where("user_group", $log_group);
		}
		
		if ($action == "delete_selected") {
			$error_count 							= 0;
			foreach ($extensions as $extension) {
				$phone_login 						= $extension;
				
				if (checkIfTenant($log_group, $goDB)) {
					$astDB->where("user_group", $log_group);
				}
				
				$astDB->where("extension", $phone_login);
				$astDB->getOne("phones");
				
				if($astDB->count > 0) {				
					$astDB->where("extension", $phone_login);
					$astDB->delete("phones");
					
					$log_id 						= log_action($goDB, 'DELETE', $log_user, $log_ip, "Deleted Phone: $phone_login", $log_group, $astDB->getLastQuery());
					
					$kamDB->where("username", $phone_login);
					$kamDB->delete("subscriber");
					
					$log_id 						= log_action($goDB, 'DELETE', $log_user, $log_ip, "Deleted Phone: $phone_login", $log_group, $kamDB->getLastQuery());
				} else {
					$error_count 					= 1;
				}
				
				if ($error_count == 0) { 
					$apiresults 					= array(
						"result" 						=> "success"
					); 
				}
				
				if ($error_count == 1) {
					$err_msg 						= error_handle("10010");
					$apiresults 					= array(
						"code" 							=> "10010", 
						"result" 						=> $err_msg, 
						"data" 							=> "$extensions"
					);
					//$apiresults = array("}result" => "Error: Delete Failed");
				}
			}
		}			
	}//end
?>
