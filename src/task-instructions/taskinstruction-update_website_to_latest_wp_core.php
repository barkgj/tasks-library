<?php

function nxs_task_instance_do_update_website_to_latest_wp_core($then_that_item, $taskid, $taskinstanceid)
{
	$result["console"][] = "ABOUT TO UPDATE WEBSITE TO LATEST CORE";
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$vps_id = $inputparameters["vps_id"];
	$studio = $inputparameters["studio"];
	$siteid = $inputparameters["siteid"];
		
	$action_url = "https://global.nexusthemes.com/api/1/prod/global-update-wpcore-for-website-by-vpsid-studio-siteid/?nxs=hosting-api&nxs_json_output_format=prettyprint";
	$action_url = nxs_addqueryparametertourl_v2($action_url, "vps_id", $vps_id, true, true);
	$action_url = nxs_addqueryparametertourl_v2($action_url, "studio", $studio, true, true);
	$action_url = nxs_addqueryparametertourl_v2($action_url, "siteid", $siteid, true, true);
	
	$result["console"][] = "INVOKING $action_url";

	$action_string = file_get_contents($action_url);
  $action_result = json_decode($action_string, true);
  if ($action_result["result"] != "OK") 
  {
  	error_log("update_website_to_latest_wp_core; nack; $action_url");
  	$result = array
		(
			"result" => "NACK",
			"action_url" => $action_url,
			"action_result" => $action_result,
		);
		return $result;
  }
  
  if ($action_result["action_result"]["evaluation"]["updatedsuccessfully"] == true)
  {
  	$result["console"][] = "WEBSITE UPDATED TO LATEST WP CORE";
  }
  else
  {
  	error_log("update_website_to_latest_wp_core; nack; $action_url");
  	
  	$result = array
		(
			"result" => "NACK",
			"action_url" => $action_url,
			"action_result" => $action_result,
		);
		return $result;
  }
  
	$result["result"] = "OK";
	return $result;
}