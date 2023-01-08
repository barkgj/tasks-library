<?php

// vpstitle = us-east-1-vps1 (see nxs.itil.configurationitems.vps, https://docs.google.com/spreadsheets/d/1MSQGTfZYVLPE06UChN0Wqa5IjOPt7OFI_mtIdYN7kR0/edit#gid=1310793155)
function nxs_task_instance_do_ensure_all_plugins_are_in_proper_state_on_website($then_that_item, $taskid, $taskinstanceid)
{
	$result["console"][] = "ENSURING ALL PLUGINS ARE IN PROPER STATE ON WEBSITE";

	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];

	$vpstitle = $inputparameters["vpstitle"];
	$studio = $inputparameters["studio"];	
	$siteid = $inputparameters["siteid"];
	
	
	$fetch_url = "https://global.nexusthemes.com/api/1/prod/global-ensure-plugins-for-studio-siteid-are-in-proper-state/?nxs=hosting-api&nxs_json_output_format=prettyprint&vpstitle={$vpstitle}&studio={$studio}&siteid={$siteid}";
	$fetch_string = file_get_contents($fetch_url);
	$fetch_result = json_decode($fetch_string, true);
	if ($fetch_result["result"] != "OK") 
	{	
		$result["result"] = "NACK"; 
		$result["exception"] = "unable to fetch url; $fetch_url";
		$result["fetch_string"] = $fetch_string;
		$result["fetch_result"] = $fetch_result;
		
		if ($fetch_string == FALSE)
		{
			$result["fetch_error"] = error_get_last();
		}
		
		return $result;
	}
	else
	{
		$result["fetch_url"] = $fetch_url;
		$result["ensure_result"] = $fetch_result;
	}
	
	$result["result"] = "OK";
	
	return $result;
}