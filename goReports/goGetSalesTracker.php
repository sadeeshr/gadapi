<?php
/**
 * @file        goGetStatisticalReports.php
 * @brief       API for Campaign Statistics
 * @copyright   Copyright (c) 2018 GOautodial Inc.
 * @author		Demian Lizandro A. Biscocho
 * @author      Alexander Jim Abenoja 
 *
 * @par <b>License</b>:
 *  This program is free software: you can redistribute it AND/or modify
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

    include_once("goAPI.php");
	
	$log_user 										= $session_user;
	$log_group 										= go_get_groupid($session_user, $astDB);
	$log_ip 										= $astDB->escape($_REQUEST['log_ip']);

	// need function go_sec_convert();
    $pageTitle 										= strtolower($astDB->escape($_REQUEST['pageTitle']));
    $fromDate 										= $astDB->escape($_REQUEST['fromDate']);
    $toDate 										= $astDB->escape($_REQUEST['toDate']);
    $campaign_id 									= $astDB->escape($_REQUEST['campaignID']);
    $request 										= $astDB->escape($_REQUEST['request']);
	//$dispo_stats 									= $astDB->escape($_REQUEST['statuses']);
	
    if (empty($fromDate)) {
    	$fromDate 									= date("Y-m-d")." 00:00:00";
	}
    
    if (empty($toDate)) {
    	$toDate 									= date("Y-m-d")." 23:59:59";
	}
		
	$defPage 										= array(
		"stats", 
		"agent_detail", 
		"agent_pdetail", 
		"dispo", 
		"call_export_report", 
		"sales_agent", 
		"sales_tracker", 
		"inbound_report"
	);

	if (empty($log_user) || is_null($log_user)) {
		$apiresults 								= array(
			"result" 									=> "Error: Session User Not Defined."
		);
	} elseif ( empty($campaign_id) || is_null($campaign_id) ) {
		$err_msg 									= error_handle("40001");
        $apiresults 								= array(
			"code" 										=> "40001",
			"result" 									=> $err_msg
		);
	} elseif (empty($fromDate) && empty($toDate)) {
		$fromDate 									= date("Y-m-d") . " 00:00:00";
		$toDate 									= date("Y-m-d") . " 23:59:59";
		//die($fromDate." - ".$toDate);									=> $err_msg
	} elseif (!in_array($pageTitle, $defPage)) {
	 	$err_msg 									= error_handle("10004");
		$apiresults 								= array(
			"code" 										=> "10004", 
			"result" 									=> $err_msg
		);
	} else {            
		// set tenant value to 1 if tenant - saves on calling the checkIfTenantf function
		// every time we need to filter out requests
		$tenant										=  (checkIfTenant ($log_group, $goDB)) ? 1 : 0;
		
		if ($tenant) {
			$astDB->where("user_group", $log_group);
		} else {
			if (strtoupper($log_group) != 'ADMIN') {
				if ($user_level > 8) {
					$astDB->where("user_group", $log_group);
				}
			}
		}
			
		// SALES TRACKER
		if ($pageTitle == "sales_tracker") {
			if ($log_group !== "ADMIN") {
				$ul 							= "AND us.user_group = '$log_group'";
			} else {
				$ul 							= "";
			}
			
			if ($request == 'outbound') {
				$outbound_query 				= "
					SELECT distinct(vl.phone_number) as phone_number, 
						vl.lead_id as lead_id, 
						vlo.call_date as call_date,
						us.full_name as agent, 
						vl.first_name as first_name,
						vl.last_name as last_name,
						vl.address1 as address,
						vl.city as city,
						vl.state as state, 
						vl.postal_code as postal,
						vl.email as email,
						vl.alt_phone as alt_phone,
						vl.comments as comments,vl.lead_id 
					FROM vicidial_log as vlo, vicidial_list as vl, vicidial_users as us 
					WHERE us.user = vlo.user AND vl.phone_number = vlo.phone_number 
					AND vl.lead_id = vlo.lead_id AND vlo.length_in_sec > '0'
					AND vlo.status in ('$statuses') AND date_format(vlo.call_date, '%Y-%m-%d %H:%i:%s') BETWEEN '$fromDate' AND '$toDate' 
					AND vlo.campaign_id = '$campaignID' $ul 
					order by vlo.call_date ASC 
					limit 2000
				";
				
				$query 							= $outbound_query;
				$outbound_result 				= "";
				$sale_num_value 				= 1;
				
				while ($row = $astDB->rawQuery($query)) {
					$sale_num[] 				= $sale_num_value;
					$outbound_result 			= $row['phone_number'];
					$call_date[] 				= $row['call_date'];
					$agent[] 					= $row['agent'];
					$lead_id[] 					= $row['lead_id'];
					$phone_number[] 			= $row['phone_number'];
					$first_name[] 				= $row['first_name'];
					$last_name[] 				= $row['last_name'];
					$address[] 					= $row['address'];
					$city[] 					= $row['city'];
					$state[] 					= $row['state'];
					$postal[] 					= $row['postal'];
					$email[] 					= $row['email'];
					$alt_phone[] 				= $row['alt_phone'];
					$comments[] 				= $row['comments'];
					$sale_num_value++;
				}
			}
		
			if ($request == 'inbound') {
				$query 							= "
					SELECT closer_campaigns FROM vicidial_campaigns 
					WHERE campaign_id = '$campaignID' 
					ORDER BY campaign_id
				";
				
				$row 							= $astDB->rawQuery($query);
				$closer_camp_array 				= explode(" ",$row['closer_campaigns']);
				$num 							= count($closer_camp_array);				
				$x								= 0;
				
				while ($x<$num) {
					if ($closer_camp_array[$x]!="-") {
						$closer_campaigns[$x]	= $closer_camp_array[$x];
					}
					
					$x++;
				}
				
				$campaign_inb_query				= "vlo.campaign_id IN ('".implode("','",$closer_campaigns)."')";
			
				$query 							= "
					SELECT distinct(vl.phone_number) as phone_number, 
						vl.lead_id as lead_id, 
						vlo.call_date as call_date,
						us.full_name as agent, 	
						vl.first_name as first_name,
						vl.last_name as last_name,
						vl.address1 as address,
						vl.city as city,
						vl.state as state, 
						vl.postal_code as postal,
						vl.email as email,
						vl.alt_phone as alt_phone,
						vl.comments as comments,
						vl.lead_id FROM vicidial_closer_log as vlo, 
						vicidial_list as vl, 
						vicidial_users as us 
					WHERE us.user = vl.user 
					AND vl.phone_number = vlo.phone_number 
					AND vl.lead_id=vlo.lead_id 
					AND vlo.length_in_sec > '0' 
					AND date_format(vlo.call_date, '%Y-%m-%d %H:%i:%s') BETWEEN '$fromDate' AND '$toDate' 
					AND $campaign_inb_query AND vlo.status in ('$statuses') $ul 
					order by vlo.call_date ASC 
					limit 2000
				";
				
				$inbound_result 				= "";
				$sale_num_value 				= 1;
				
				while ($row = $astDB->rawQuery($query)) {
					$sale_num[] 				= $sale_num_value;
					$inbound_result 			= $row['phone_number'];
					$call_date[] 				= $row['call_date'];
					$agent[] 					= $row['agent'];
					$lead_id[] 					= $row['lead_id'];
					$phone_number[] 			= $row['phone_number'];
					$first_name[] 				= $row['first_name'];
					$last_name[] 				= $row['last_name'];
					$address[] 					= $row['address'];
					$city[] 					= $row['city'];
					$state[] 					= $row['state'];
					$postal[] 					= $row['postal'];
					$email[] 					= $row['email'];
					$alt_phone[] 				= $row['alt_phone'];
					$comments[] 				= $row['comments'];
					$sale_num_value++;
				}
			}
			
			//$return['TOPsorted_output']		= $TOPsorted_output;
			//$return['file_output']			= $file_output;
			$apiresults 						= array(
				"outbound_result" 					=> $outbound_result, 
				"inbound_result" 					=> $inbound_result, 
				"sale_num" 							=> $sale_num, 
				"call_date" 						=> $call_date, 
				"agent" 							=> $agent, 
				"phone_number" 						=> $phone_number, 
				"lead_id" 							=> $lead_id, 
				"first_name" 						=> $first_name, 
				"last_name" 						=> $last_name,
				"address" 							=> $address, 
				"city" 								=> $city, 
				"state" 							=> $state, 
				"postal" 							=> $postal, 
				"email" 							=> $email, 
				"alt_phone" 						=> $alt_phone, 
				"comments" 							=> $comments,
				"query" 							=> $outbound_query
			);
			
			return $apiresults;
		}
	}

?>
