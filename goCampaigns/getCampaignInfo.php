<?php
    #######################################################
    #### Name: getCampaignInfo.php	               ####
    #### Description: API to get specific campaign     ####
    #### Version: 0.9                                  ####
    #### Copyright: GOAutoDial Inc. (c) 2011-2014      ####
    #### Written by: Jerico James Milo                 ####
    #### License: AGPLv2                               ####
    #######################################################
    include_once("../goFunctions.php");
    
    // POST or GET Variables
    $campaign_id = mysqli_real_escape_string($link, $_REQUEST['campaign_id']);
    $log_user = mysqli_real_escape_string($link, $_REQUEST['log_user']);
    $log_group = mysqli_real_escape_string($link, $_REQUEST['log_group']);
    $log_ip = mysqli_real_escape_string($link, $_REQUEST['log_ip']);
	$session_user = mysqli_real_escape_string($link, $_REQUEST['session_user']);

    // Check campaign_id if its null or empty
	if(empty($campaign_id) || empty($session_user)) {
		$err_msg = error_handle("40001");
		$apiresults = array("code" => "40001", "result" => $err_msg); 
	} else {
		
		$groupId = go_get_groupid($session_user);
		
		if (!checkIfTenant($groupId)) {
			$ul = "WHERE campaign_id='$campaign_id'";
		} else { 
			$ul = "WHERE campaign_id='$campaign_id' AND user_group='$groupId'";  
		}
		
		//$query = "SELECT campaign_id,campaign_name,dial_method,active FROM vicidial_campaigns $ul ORDER BY campaign_id LIMIT 1;";
		$query = "SELECT * FROM vicidial_campaigns $ul ORDER BY campaign_id LIMIT 1;";
		$rsltv = mysqli_query($link, $query);
		$countResult = mysqli_num_rows($rsltv);
		
		if($countResult > 0) {
			$dynamic_cid_COL = '';
			$checkColumn = mysqli_query($linkgo, "SHOW COLUMNS FROM `go_campaigns` LIKE 'dynamic_cid'");
			$columnRows = mysqli_num_rows($checkColumn);
			if ($columnRows > 0) {
				$dynamic_cid_COL = ", dynamic_cid";
			}
			
			while($fresults = mysqli_fetch_array($rsltv, MYSQLI_ASSOC)){
				$queryGoCampaign = "SELECT campaign_type,custom_fields_launch,custom_fields_list_id,url_tab_first_title,url_tab_first_url,url_tab_second_title,url_tab_second_url,location_id $dynamic_cid_COL FROM go_campaigns WHERE campaign_id='$campaign_id' LIMIT 1";
				$rsltvGoCampaign = mysqli_query($linkgo, $queryGoCampaign);
				while($typeresults = mysqli_fetch_array($rsltvGoCampaign, MYSQLI_ASSOC)){
					$campaign_type = $typeresults['campaign_type'];
					$custom_fields_launch = $typeresults['custom_fields_launch'];
					$custom_fields_list_id = $typeresults['custom_fields_list_id'];
					$url_tab_first_title = $typeresults['url_tab_first_title'];
					$url_tab_first_url = $typeresults['url_tab_first_url'];
					$url_tab_second_title = $typeresults['url_tab_second_title'];
					$url_tab_second_url = $typeresults['url_tab_second_url'];
					$location_id = $typeresults['location_id'];
					if ($dynamic_cid_COL !== '') {
						$dynamic_cid = $typeresults['dynamic_cid'];
					}
				}
				
				if($campaign_type == "SURVEY"){
					$queryRA = "SELECT * from vicidial_remote_agents WHERE campaign_id='$campaign_id' LIMIT 1";
					$rsltvRA = mysqli_query($link, $queryRA);
					while($RAresults = mysqli_fetch_array($rsltvRA, MYSQLI_ASSOC)){
						$numberoflines= $RAresults['number_of_lines'];
					}
				}
				
				$custom_fields_launch = (gettype($custom_fields_launch) != 'NULL') ? $custom_fields_launch : 'ONCALL';
				$custom_fields_list_id = (gettype($custom_fields_list_id) != 'NULL') ? $custom_fields_list_id : '';
				$url_tab_first_title = (gettype($url_tab_first_title) != 'NULL') ? $url_tab_first_title : '';
				$url_tab_first_url = (gettype($url_tab_first_url) != 'NULL') ? $url_tab_first_url : '';
				$url_tab_second_title = (gettype($url_tab_second_title) != 'NULL') ? $url_tab_second_title : '';
				$url_tab_second_url = (gettype($url_tab_second_url) != 'NULL') ? $url_tab_second_url : '';
				$location_id = (gettype($location_id) != 'NULL') ? $location_id : '';
				$dynamic_cid = (gettype($dynamic_cid) != 'NULL') ? $dynamic_cid : '';
				$apiresults = array(
									"result" => "success",
									"data" => $fresults,
									"campaign_type" => $campaign_type,
									"custom_fields_launch" => $custom_fields_launch,
									'custom_fields_list_id' => $custom_fields_list_id,
									'url_tab_first_title' => $url_tab_first_title,
									'url_tab_first_url' => $url_tab_first_url,
									'url_tab_second_title' => $url_tab_second_title,
									'url_tab_second_url' => $url_tab_second_url,
									'number_of_lines' => $numberoflines,
									'location_id' => $location_id,
									'dynamic_cid' => $dynamic_cid
								);
				//$apiresults = array("result" => "success", "data" => $fresults);
			}
			
			$log_id = log_action($linkgo, 'VIEW', $log_user, $log_ip, "Viewed the info of campaign id: $campaign_id", $log_group);
			
		} else {
			$err_msg = error_handle("41004", "campaign_id");
			$apiresults = array("code" => "41004", "result" => $err_msg);
		}
	}//end
?>
