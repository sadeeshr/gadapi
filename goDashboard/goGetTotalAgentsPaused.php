<?php
 /**
 * @file 		goGetTotalAgentsPaused.php
 * @brief 		API for Dashboard
 * @copyright 	Copyright (c) 2018 GOautodial Inc.
 * @author		Demian Lizandro A. Biscocho
 * @author     	Chris Lomuntad
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
	//$log_ip 										= $astDB->escape($_REQUEST["log_ip"]);
	$campaigns 										= allowed_campaigns($log_group, $goDB, $astDB);
    $agentstatus									= "PAUSED";

    // ERROR CHECKING 
	if (!isset($log_user) || is_null($log_user)){
		$apiresults 								= array(
			"result" 									=> "Error: Session User Not Defined."
		);
	} elseif (is_array($campaigns)) {	
		$data										= $astDB
			->where("campaign_id", $campaigns, "IN")
			->where("status", array($agentstatus), "IN")
			->getValue("vicidial_live_agents", "count(*)");
		
		$apiresults 								= array(
			"result" 									=> "success", 
			"data" 										=> $data
		);	
	}
	
?>
