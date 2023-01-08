<?php

function nxs_task_instance_do_reset_website_roles_and_capabilities($then_that_item, $taskid, $taskinstanceid)
{
	error_log("beep");
	
	$result["console"][] = "ABOUT TO RESET WEBSITE ROLES AND CAPABILITIES";
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$vpstitle = $inputparameters["vpstitle"];
	$studio = $inputparameters["studio"];
	$siteid = $inputparameters["siteid"];
		
	error_log("beep2");
	
	$action_url = "https://global.nexusthemes.com/api/1/prod/global-reset-roles-and-capabilities-for-vpstitle-studio-siteid/?nxs=hosting-api&nxs_json_output_format=prettyprint";
	$action_url = nxs_addqueryparametertourl_v2($action_url, "vpstitle", $vpstitle, true, true);
	$action_url = nxs_addqueryparametertourl_v2($action_url, "studio", $studio, true, true);
	$action_url = nxs_addqueryparametertourl_v2($action_url, "siteid", $siteid, true, true);
	
	$result["console"][] = "INVOKING $action_url";

	$action_string = file_get_contents($action_url);
  $action_result = json_decode($action_string, true);
  if ($action_result["result"] != "OK") 
  {
  	$result = array
		(
			"result" => "NACK",
			"nack_details" => array
			(
				"message" => "nack detected while invoking url",
				"action_url" => $action_url,
				"action_result" => $action_result
			)
		);
		return $result;
  }
  
  $result["console"][] = "ROLES AND CAPABILITIES RESETTED";
  
	$result["result"] = "OK";
	
	return $result;
}