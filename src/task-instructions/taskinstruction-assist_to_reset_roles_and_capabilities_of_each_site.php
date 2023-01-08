<?php

function nxs_task_instance_do_assist_to_reset_roles_and_capabilities_of_each_site($then_that_item, $taskid, $taskinstanceid)
{
	// only do this if the task is started
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$state = $instancemeta["state"];
	if ($state != "STARTED")
	{
		$result["console"][] = "IGNORING TASK INSTRUCTION nxs_task_instance_do_assist_to_reset_roles_and_capabilities_of_each_site BECAUSE OF STATE; $state";
		$result["result"] = "OK";
		return $result;
	}
	
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
	
	$args = array();
	$args["items"] = array();

	foreach ($fetch_result["mappings"] as $ignored => $mappings)
	{		
		foreach ($mappings as $mapping)
		{
			$taskid_of_instances_to_create = 210;
			$vpstitle = $mapping["vps_title"];
			$studio = $mapping["studio"];
			$siteid = $mapping["siteid"];
			
			$args["items"][] = array
			(
				"taskid" => $taskid_of_instances_to_create,
				"vpstitle" => $vpstitle,
				"studio" => $studio,
				"siteid" => $siteid,
				"createdby_taskid" => $taskid,
				"createdby_taskinstanceid" => $taskinstanceid
			);
			
			$result["console"][] = "QUEUEING TASK INSTANCE FOR TASK ID {$taskid_of_instances_to_create} FOR $vpstitle $studio $siteid";
		}
	}
	
	if ($_REQUEST["do_confirmed"] == "")
	{
		$result["console"][] = "READY? CLICK HERE:";
		$currenturl = nxs_geturlcurrentpage();
		$url = $currenturl;
		$url = nxs_addqueryparametertourl_v2($url, "do_confirmed", "true", true, true);
		$result["console"][] = "<a href='$url'>Do it!</a>";
	}
	else
	{
		$args_json = json_encode($args);	
			
		$action_args = array
		(
			"url" => "https://global.nexusthemes.com/api/1/prod/create-bulk-task-instances/",
			"method" => "POST",
			"postargs" => array
			(
				"nxs" => "businessprocess-api",
				"nxs_json_output_format" => "prettyprint",
				"args_json" => $args_json
			)
		);		
		$action_string = nxs_geturlcontents($action_args);
		
		$action_result = json_decode($action_string, true);
		if ($action_result["result"] != "OK") 
		{
			$result["console"][] = "OUTPUT:" . htmlentities($action_string);
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
		
	$result["result"] = "OK";
	
	return $result;
}