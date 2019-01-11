<?php
 /**
 * @file 		goGetCallRecordingList.php
 * @brief 		API for Call Recordings
 * @copyright 	Copyright (c) 2018 GOautodial Inc.
 * @author		Demian Lizandro A. Biscocho
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
	
	$log_user 							= $session_user;
	$log_group 							= go_get_groupid($session_user, $astDB); 
	$log_ip 							= $astDB->escape($_REQUEST['log_ip']);	
	$limit 								= "50";
	
	### POST or GET Variables
    $requestDataPhone 					= $astDB->escape($_REQUEST['requestDataPhone']);
	$start_filterdate 					= $astDB->escape($_REQUEST['start_filterdate']);
	$end_filterdate 					= $astDB->escape($_REQUEST['end_filterdate']);
	$agent_filter 						= $astDB->escape($_REQUEST['agent_filter']);
	
	if (!isset($session_user) || is_null($session_user)){
		$apiresults 					= array(
			"result" 						=> "Error: Session User Not Defined."
		);
	} elseif (isset($_REQUEST['limit'])) {
		$limit 							= $astDB->escape($_REQUEST['limit']);
	} else {	
		if (checkIfTenant($log_group, $goDB)) {
			$ul							= "AND vl.user_group = '$log_group'";
		} else {
			if($log_group !== "ADMIN"){
				$stringv 				= go_getall_allowed_users($log_group);
				$ul 					= "AND rl.user IN ($stringv)";
			} else {
				$ul 					= "";
			}
		} 	
			
		if (!empty($requestDataPhone)) {
			$sqlPhone 					= "AND vl.phone_number LIKE '$requestDataPhone%'";
			$limit 						= "500";
		} else{
			$sqlPhone 					= "";
		}

		if ($start_filterdate != "" && $end_filterdate != "" && $start_filterdate != $end_filterdate){
			$limit 						= "1000";
			$filterdate 				= "AND date_format(rl.end_time, '%Y-%m-%d %H:%i:%s') BETWEEN '$start_filterdate' AND '$end_filterdate'";
		} else {
			$filterdate 				= "";
		}

		if (!empty($agent_filter)) {
			$limit 						= "1000";
			$filteragent 				= "AND rl.user = '$agent_filter'";
		} else {
			$filteragent 				= "";
		}


		$query = "SELECT 
				CONCAT(vl.first_name,' ',vl.last_name) AS full_name, 
				rl.vicidial_id, 
				vl.last_local_call_time, 
				vl.phone_number, 
				rl.length_in_sec, 
				rl.filename, 
				rl.location, 
				rl.lead_id, 
				rl.user, 
				rl.start_time, 
				rl.end_time, 
				rl.recording_id, 
				rl.b64encoded 
			FROM recording_log rl, vicidial_list vl 
			WHERE rl.lead_id = vl.lead_id
			$sqlPhone 
			$filterdate 
			$filteragent 
			$ul 
			ORDER BY rl.start_time DESC
			LIMIT $limit;";

		$rsltv 								= $astDB->rawQuery($query);
		
		if ($astDB->count > 0) {
			foreach ($rsltv as $fresults) {
				$location 						= $fresults['location'];
				
				if (strlen($location) > 2) {
					$URLserver_ip 				= $location;
					$URLserver_ip 				= preg_replace('/http:\/\//i', '', $URLserver_ip);
					$URLserver_ip 				= preg_replace('/https:\/\//i', '', $URLserver_ip);
					$URLserver_ip 				= preg_replace('/\/.*/i', '', $URLserver_ip);
					//$stmt="SELECT count(*) FROM servers WHERE server_ip='$URLserver_ip';";
					$astDB->where('server_ip', $URLserver_ip);
					$astDB->get('servers');
					
					if ($astDB->count > 0) {
						$cols 					= array(
							"recording_web_link",
							"alt_server_ip",
							"external_server_ip"
						);
						
						$astDB->where('server_ip', $URLserver_ip);
						$rsltx 					= $astDB->get('servers', NULL, $cols);
						
						if (preg_match("/ALT_IP/i", $rsltx['recording_web_link'])) {
							$location 			= preg_replace("/$URLserver_ip/i", "{$rowx['alt_server_ip']}", $location);
						}
						if (preg_match("/EXTERNAL_IP/i", $rowx['recording_web_link'])) {
							$location 			= preg_replace("/$URLserver_ip/i", "{$rowx['external_server_ip']}", $location);
						}
					}
				}
				
				$dataLeadId[] 					= $fresults['lead_id'];
				$dataUniqueid[] 				= $fresults['vicidial_id'];
				$dataStatus[] 					= $fresults['status'];
				$dataUser[] 					= $fresults['user'];
				$dataPhoneNumber[] 				= $fresults['phone_number'];
				$dataFullName[] 				= $fresults['full_name'];
				$dataLastLocalCallTime[] 		= $fresults['last_local_call_time'];
				$dataStartLastLocalCallTime[] 	= $fresults['start_time'];
				$dataEndLastLocalCallTime[] 	= $fresults['end_time'];
				$dataLocation[] 				= $location;
				$dataRecordingID[] 				= $fresults['recording_id'];
				$dataB64encoded[]				= $fresults['b64encoded'];
				
			}

			//$query1 = "SELECT count(*) AS `cnt` FROM recording_log WHERE lead_id='{$fresults['lead_id']}';";
			$astDB->where('lead_id', $fresults['lead_id']);
			$astDB->get('recording_log');
			$dataCount	 						= $astDB->getRowCount();

			$log_id 							= log_action($goDB, 'VIEW', $log_user, $log_ip, "View the Call Recording List", $log_group);

			$apiresults 						= array(
				"result" 							=> "success",
				"query" 							=> $query,
				"cnt" 								=> $dataCount,
				"lead_id" 							=> $dataLeadId,
				"uniqueid" 							=> $dataUniqueid,
				"status" 							=> $dataStatus,
				"users" 							=> $dataUser,
				"phone_number"	 					=> $dataPhoneNumber,
				"full_name" 						=> $dataFullName,
				"last_local_call_time" 				=> $dataLastLocalCallTime,
				"start_last_local_call_time" 		=> $dataStartLastLocalCallTime,
				"end_last_local_call_time" 			=> $dataEndLastLocalCallTime,
				"location" 							=> $dataLocation,
				"recording_id" 						=> $dataRecordingID,
				"b64encoded" 						=> $dataB64encoded,
				"query" 							=> $query
			);
		} else {
			$apiresults 						= array(
				"result" 							=> "success"
			);
		}
	}

?>
