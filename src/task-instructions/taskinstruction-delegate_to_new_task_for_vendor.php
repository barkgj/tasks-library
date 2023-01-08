<?php

function nxs_task_instance_do_delegate_to_new_task_for_vendor($then_that_item, $taskid, $taskinstanceid)
{
	$result["console"][] = "STARTING DELEGATION TO NEW TASK FOR VENDOR";

	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$state = $instancemeta["state"];
	$inputparameters = $instancemeta["inputparameters"];
	$subject_original_ticket = $inputparameters["subject_original_ticket"];
	$vendor_id = $inputparameters["vendor_id"];
	if ($vendor_id == "")
	{
		$result["console"][] = "nxs_task_instance_do_delegate_to_new_task_for_vendor; no vendor_id found?";
	}
	else
	{
		// lookup vendor in vendor ixplatform
		$fetch_url = "https://global.nexusthemes.com/api/1/prod/get-vendor-by-id/?nxs=vendor-api&nxs_json_output_format=prettyprint&id={$vendor_id}";
		$fetch_string = file_get_contents($fetch_url);
		$fetch_result = json_decode($fetch_string, true);
		if ($fetch_result["result"] != "OK")
		{
			$result = array
			(
				"result" => "NACK",
				"details" => "unable to fetch fetch_url; $fetch_url",
			);
			return $result;
		}
		$messageshandledby_taskid = $fetch_result["props"]["messageshandledby_taskid"];
		if ($messageshandledby_taskid == "")
		{
			$result = array
			(
				"result" => "NACK",
				"details" => "messageshandledby_taskid not set for vendor; $fetch_url",
			);
			return $result;
		}
		
		$create_subresult = nxs_tasks_createtaskinstance_byinvokingapi($messageshandledby_taskid, $inputparameters, $taskid, $taskinstanceid);
		if ($create_subresult["result"] != "OK") 
		{
			return $create_subresult;
		}
		
		$result["console"][] = "TASK INSTANCE CREATED WITH TASKID {$messageshandledby_taskid}";
	}
	
	$result["result"] = "OK";
	
	return $result;
}