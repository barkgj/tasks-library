<?php

function nxs_nonce_create()
{
	
}

function nxs_task_instance_do_sleep_task_instance($then_that_item, $taskid, $taskinstanceid)
{
	$md5 = md5(json_encode($then_that_item));

	$validvalue = "confirm_{$md5}";
	$result["console"][] = $_REQUEST["sleep_action"];
	$result["console"][] = $_REQUEST["validvalue"];
	//$result["console"][] = $_REQUEST["sleep_action"] . " versus " . $validvalue;
	
	if ($_REQUEST["sleep_action"] == $validvalue)
	{
		$result["console"][] = "SLEEPING TASK INSTANCE";
		
		$instance = nxs_task_getinstance($taskid, $taskinstanceid);
		
		$action_url = "https://global.nexusthemes.com/api/1/prod/sleep-task-instance/?nxs=businessprocess-api&nxs_json_output_format=prettyprint&taskid={$taskid}&taskinstanceid={$taskinstanceid}";
	
		// do it!
		$action_string = file_get_contents($action_url);
		$action_result = json_decode($action_string, true);
		if ($action_result["result"] != "OK") 
		{
			$result = array
			(
				"result" => "NACK",
				"details" => "unable to fetch action_url; $action_url",
			);
			return $result;
		}
	
		$result["console"][] = "TASK INSTANCE IS NOW SLEEPING";
		
		$currenturl = nxs_geturlcurrentpage();
		$url = $currenturl;
		$url = nxs_removequeryparameterfromurl($url, "sleep_action");

		echo "task instance is now sleeping, click <a href='$url'>here</a> to refresh the page";
		die();
	}
	else
	{
		$currenturl = nxs_geturlcurrentpage();
		$url = $currenturl;
		$url = nxs_addqueryparametertourl_v2($url, "sleep_action", $validvalue, false, true);
		$result["console"][] = "CLICK TO SLEEP; <a href='$url'>HERE</a>";
	}
	
	$result["result"] = "OK";
	
	return $result;
}