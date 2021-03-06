<?php
 /**
 * @file 		goAddDisposition.php
 * @brief 		API for Dispositions
 * @copyright 	Copyright (c) 2018 GOautodial Inc.
 * @author		Demian Lizandro A. Biscocho
 * @author     	Chris Lomuntad
 * @author     	Jeremiah Sebastian Samatra 
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
*/

    include_once ("goAPI.php");
 
	$log_user 										= $session_user;
	$log_group 										= go_get_groupid($session_user, $astDB); 
	$log_ip 										= $astDB->escape($_REQUEST['log_ip']);    
	$campaigns 										= allowed_campaigns($log_group, $goDB, $astDB);
	
    // POST or GET Variables
    $campaign_id 									= $astDB->escape($_REQUEST['campaign_id']);
	$category 										= "UNDEFINED"; //$_REQUEST['category'];
	$userid 										= $astDB->escape($_REQUEST['userid']);
	$status 										= $astDB->escape($_REQUEST['status']);	
	$status_name 									= $astDB->escape($_REQUEST['status_name']);
	$selectable 									= $astDB->escape($_REQUEST['selectable']);
	$human_answered 								= $astDB->escape($_REQUEST['human_answered']);
	$sale											= $astDB->escape($_REQUEST['sale']);
	$dnc 											= $astDB->escape($_REQUEST['dnc']);
	$customer_contact 								= $astDB->escape($_REQUEST['customer_contact']);
	$not_interested 								= $astDB->escape($_REQUEST['not_interested']);
	$unworkable 									= $astDB->escape($_REQUEST['unworkable']);
	$scheduled_callback 							= $astDB->escape($_REQUEST['scheduled_callback']);
	
	$color 											= $astDB->escape($_REQUEST['color']);
	$priority 										= $astDB->escape($_REQUEST['priority']);
	$type 											= $astDB->escape($_REQUEST['type']);

    // Default values 
    $defVal 										= array(
		"Y",
		"N"
	);
	
	if (!$color) { 
		$color 										= "#b5b5b5"; 
	}
	if (!$priority) { 
		$priority 									= 1; 
	}
	if (!$type) { 
		$type 										= 'CUSTOM'; 
	}


    // ERROR CHECKING 
	if (!isset($log_user) || is_null($log_user)){
		$apiresults 								= array(
			"result" 									=> "Error: Session User Not Defined."
		);
	} elseif (empty($campaign_id) || is_null($campaign_id)) {
		$err_msg 									= error_handle("40001");
        $apiresults 								= array(
			"code" 										=> "40001",
			"result" 									=> $err_msg
		);
    }  elseif (empty($status) || is_null($status)) {
		$apiresults 								= array(
			"result" 									=> "Error: Set a value for status."
		);
	} elseif (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', $status_name) || $status_name == null){
		$err_msg 									= error_handle("10003", "status_name");
		$apiresults 								= array(
			"code" 										=> "10003", 
			"result" 									=> $err_msg
		);
		//$apiresults = array("result" 			=> "Error: Special characters found in status name and must not be empty");
	} elseif (!in_array($scheduled_callback,$defVal)) {
		$err_msg 									= error_handle("10003", "scheduled_callback");
		$apiresults 								= array(
			"code" 										=> "10003", 
			"result" 									=> $err_msg
		);
		//$apiresults = array("result" 			=> "Error: Default value for scheduled_callback is Y or N only.");
	} elseif (!in_array($unworkable,$defVal)) {
		$err_msg 									= error_handle("10003", "unworkable");
		$apiresults 								= array(
			"code" 										=> "10003", 
			"result" 									=> $err_msg
		);
		//$apiresults = array("result" 			=> "Error: Default value for unworkable is Y or N only.");
	} elseif (!in_array($selectable,$defVal)) {
		$err_msg 									= error_handle("10003", "selectable");
		$apiresults 								= array(
			"code" 										=> "10003", 
			"result" 									=> $err_msg
		);
		//$apiresults = array("result" 			=> "Error: Default value for selectable is Y or N only.");
	} elseif (!in_array($human_answered,$defVal)) {
		$err_msg 									= error_handle("10003", "human_answered");
		$apiresults 								= array(
			"code" 										=> "10003", 
			"result" 									=> $err_msg
		);
		//$apiresults = array("result" 			=> "Error: Default value for human_answered is Y or N only.");
	} elseif (!in_array($sale,$defVal)) {
		$err_msg 									= error_handle("10003", "sale");
		$apiresults 								= array(
			"code" 										=> "10003", 
			"result" 									=> $err_msg
		);
		//$apiresults = array("result" 			=> "Error: Default value for sale is Y or N only.");
	} elseif (!in_array($dnc,$defVal)) {
		$err_msg 									= error_handle("10003", "dnc");
		$apiresults 								= array(
			"code" 										=> "10003", 
			"result" 									=> $err_msg
		);
		//$apiresults = array("result" 			=> "Error: Default value for dnc is Y or N only.");
	} elseif (!in_array($customer_contact,$defVal)) {
		$err_msg 									= error_handle("10003", "customer_contact");
		$apiresults 								= array(
			"code" 										=> "10003", 
			"result" 									=> $err_msg
		);
		//$apiresults = array("result" 			=> "Error: Default value for customer_contact is Y or N only.");
	} elseif (!in_array($not_interested,$defVal)) {
		$err_msg 									= error_handle("10003", "not_interested");
		$apiresults 								= array(
			"code" 										=> "10003", 
			"result" 									=> $err_msg
		);
		//$apiresults = array("result" 			=> "Error: Default value for not_interested is Y or N only.");
	} else {					
		if (in_array($campaign_id, $campaigns) || $campaign_id == 'ALL') {			
			$astDB->where('status', $status);
			$astDB->get('vicidial_statuses', null, 'status');
			
			if ($astDB->count <= 0) {
				if ($campaign_id == 'ALL') {
					$astDB->where("status", $status);
					$astDB->get("vicidial_campaign_statuses", NULL, "status");
					
					if($astDB->count <= 0) {
						foreach ($campaigns["campaign_id"] as $key => $campaignid) {
							$data					= array(
								"status"				=> $status, 	
								"status_name"			=> $status_name,
								"selectable"			=> $selectable, 
								"campaign_id"			=> $campaignid,
								"human_answered"		=> $human_answered,
								"category"				=> $category,
								"sale"					=> $sale,
								"dnc"					=> $dnc,
								"customer_contact"		=> $customer_contact,
								"not_interested"		=> $not_interested,
								"unworkable"			=> $unworkable,
								"scheduled_callback"	=> $scheduled_callback
							);
							
							$astDB->insert("vicidial_campaign_statuses", $data);
							$log_id 				= log_action($goDB, 'ADD', $log_user, $log_ip, "Added a New Disposition $status on Campaign $campaignid", $log_group, $astDB->getLastQuery());							
							
							$tableQuery 			= "SHOW tables LIKE 'go_statuses';";
							$checkTable 			= $goDB->rawQuery($tableQuery);

							if ($checkTable) {
								$datago				= array(
									"status"			=> $status, 	
									"campaign_id"		=> $campaignid,
									"priority"			=> $priority,
									"color"				=> $color,
									"type"				=> $type
								);
								
								$qgo_insert			= $goDB->insert("go_statuses", $datago);
								$log_id 			= log_action($goDB, 'ADD', $log_user, $log_ip, "Added a New Disposition $status on Campaign $campaignid", $log_group, $goDB->getLastQuery());							
							}
						}					
											
						$apiresults 				= array(
							"result" 					=> "success"
						);						
					} else {
						$err_msg 					= error_handle("41004", "status. Campaign Status already exists");
						$apiresults					= array(
							"code" 						=> "41004", 
							"result" 					=> $err_msg
						);
					}						
				} else {
					$astDB->where("campaign_id", $campaign_id);
					$astDB->where("status", $status);
					$astDB->get("vicidial_campaign_statuses", NULL, "status");
					
					if($astDB->count <= 0) {						
						$data						= array(
							"status"					=> $status, 	
							"status_name"				=> $status_name,
							"selectable"				=> $selectable, 
							"campaign_id"				=> $campaign_id,
							"human_answered"			=> $human_answered,
							"category"					=> $category,
							"sale"						=> $sale,
							"dnc"						=> $dnc,
							"customer_contact"			=> $customer_contact,
							"not_interested"			=> $not_interested,
							"unworkable"				=> $unworkable,
							"scheduled_callback"		=> $scheduled_callback
						);
						
						$astDB->insert("vicidial_campaign_statuses", $data);
						$log_id 					= log_action($goDB, 'ADD', $log_user, $log_ip, "Added a New Disposition $status on Campaign $campaign_id", $log_group, $astDB->getLastQuery());							
						
						$tableQuery 				= "SHOW tables LIKE 'go_statuses';";
						$checkTable 				= $goDB->rawQuery($tableQuery);

						if ($checkTable) {
							$datago					= array(
								"status"				=> $status, 	
								"campaign_id"			=> $campaign_id,
								"priority"				=> $priority,
								"color"					=> $color,
								"type"					=> $type
							);
							
							$qgo_insert				= $goDB->insert("go_statuses", $datago);
							$log_id 				= log_action($goDB, 'ADD', $log_user, $log_ip, "Added a New Disposition $status on Campaign $campaign_id", $log_group, $goDB->getLastQuery());							
						}
						
						$apiresults 				= array(
							"result" 					=> "success"
						);
					} else {
						$err_msg 					= error_handle("41004", "status. Campaign Status already exists");
						$apiresults					= array(
							"code" 						=> "41004", 
							"result" 					=> $err_msg
						);
					}					
				}
			} else {
				$err_msg 							= error_handle("41004", "status. Status already exists in the default statuses");
				$apiresults 						= array(
					"code" 								=> "41004", 
					"result" 							=> $err_msg
				);
			}
		} else {		
			$err_msg 								= error_handle("10108", "status. No campaigns available");
			$apiresults								= array(
				"code" 									=> "10108", 
				"result" 								=> $err_msg
			);
		}
	}
	
?>
