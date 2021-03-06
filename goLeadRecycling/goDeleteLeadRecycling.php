<?php
 /**
 * @file        goAddLeadRecycling.php
 * @brief 	    API for Adding Lead Recycling
 * @copyright 	Copyright (C) GOautodial Inc.
 * @author	    Alexander Abenoja  <alex@goautodial.com>
 * @author     	Chris Lomuntad  <chris@goautodial.com>
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
	$log_ip 										= $astDB->escape($_REQUEST["log_ip"]);
	$campaigns 										= allowed_campaigns($log_group, $goDB, $astDB);
	
    ### POST or GET Variables
	$campaign_id 									= $astDB->escape($_REQUEST["campaign_id"]);	
	$recycle_id 									= $astDB->escape($_REQUEST["recycle_id"]);
    
    ### Check Campaign ID if its null or empty
	if (empty($log_user) || is_null($log_user)) {
		$apiresults 								= array(
			"result" 									=> "Error: Session User Not Defined."
		);
	} elseif (empty($campaign_id) || is_null($campaign_id)) {
		$err_msg 									= error_handle("40001");
        $apiresults 								= array(
			"code" 										=> "40001",
			"result" 									=> $err_msg
		);
	} elseif (is_array($campaigns)) {
		if (in_array($campaign_id, $campaigns)) {
			$astDB->where("campaign_id", $campaign_id);
			$astDB->get("vicidial_lead_recycle");
			
			if($astDB->count > 0) {
				if  (empty($recycle_id) || is_null($recycle_id)) {
					$astDB->where("campaign_id", $campaign_id);
					$astDB->delete("vicidial_lead_recycle");
					$log_id 					= log_action($goDB, "DELETE", $log_user, $log_ip, "Deleted All Lead Recycling under Campaign ID: $campaign_id", $log_group, $astDB->getLastQuery());
					
					$apiresults 				= array(
						"result" 					=> "success"
					);						
				} else {
					// check lead recycle id if it exists
					$astDB->where("recycle_id", $recycle_id);
					$astDB->where("campaign_id", $campaign_id);
					$astDB->get("vicidial_lead_recycle");
					
					if($astDB->count > 0) {
						$astDB->where("recycle_id", $recycle_id);
						$astDB->where("campaign_id", $campaign_id);
						$astDB->delete("vicidial_lead_recycle");
						$log_id 				= log_action($goDB, "DELETE", $log_user, $log_ip, "Deleted Lead Recycling ID: $recycle_id", $log_group, $astDB->getLastQuery());						

						$apiresults 			= array(
							"result" 				=> "success"
						);							
					} else {
						$apiresults 			= array(
							"result" 				=> "Error: Lead Recycling ID doesn't exist"
						);						
					}
				}										
			} else {
				$apiresults 					= array(
					"result" 						=> "Error: Campaign doesn't exist"
				);
			}
		} else {
			$err_msg 							= error_handle("10001", "Insufficient permision");
			$apiresults 						= array(
				"code" 								=> "10001", 
				"result" 							=> $err_msg
			);			
		}
	} else {
		$err_msg 								= error_handle("10001", "Insufficient permision");
		$apiresults 							= array(
			"code" 									=> "10001", 
			"result" 								=> $err_msg
		);			
	}
	
?>
