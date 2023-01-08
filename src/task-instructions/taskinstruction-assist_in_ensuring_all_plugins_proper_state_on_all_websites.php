<?php

function nxs_task_instance_do_assist_in_ensuring_all_plugins_proper_state_on_all_websites($then_that_item, $taskid, $taskinstanceid)
{
	$result["console"][] = "QUERYING ALL SITES IN OUR INFRA";
	
	$fetch_url = "https://global.nexusthemes.com/api/1/prod/global-domain-mappings/?nxs=hosting-api&nxs_json_output_format=prettyprint&type_filter=website|studio|vps";
	$fetch_string = file_get_contents($fetch_url);
	$fetch_result = json_decode($fetch_string, true);
	if ($fetch_result["result"] != "OK") 
	{ 
		$result["result"] = "NACK"; 
		$result["exception"] = "unable to fetch url; $fetch_url";
		return $result;
	}
	
	foreach ($fetch_result["mappings"] as $ignored => $mappings)
	{		
		foreach ($mappings as $mapping)
		{
			$vpstitle = $mapping["vps_title"];
			$studio = $mapping["studio"];
			$siteid = $mapping["siteid"];
			
			// 
			$action_url = "https://global.nexusthemes.com/api/1/prod/create-task-instance/?nxs=businessprocess-api&nxs_json_output_format=prettyprint";
			$action_url = nxs_addqueryparametertourl_v2($action_url, "businessprocesstaskid", "200", true, true);
			$action_url = nxs_addqueryparametertourl_v2($action_url, "vpstitle", $vpstitle, true, true);
			$action_url = nxs_addqueryparametertourl_v2($action_url, "studio", $studio, true, true);
			$action_url = nxs_addqueryparametertourl_v2($action_url, "siteid", $siteid, true, true);
			$action_url = nxs_addqueryparametertourl_v2($action_url, "createdby_taskid", $taskid, true, true);
			$action_url = nxs_addqueryparametertourl_v2($action_url, "createdby_taskinstanceid", $taskinstanceid, true, true);
			
			$result["console"][] = "CREATING TASK INSTANCE TO ENSURE PLUGINS ARE IN PROPER STATE FOR $vpstitle $studio $siteid";
			
			$action_string = file_get_contents($action_url);
			$action_result = json_decode($action_string, true);
			if ($action_result["result"] != "OK") 
			{
				$result["result"] = "NACK"; 
				$result["exception"] = "ERR CREATING TASK";
				$result["exception_details"] = array
				(
					"action_url" => $action_url,
					"action_result" => $action_result,
				);
				return $result;
			}
		}
	}
	
	$result["result"] = "OK";
	
	return $result;
}