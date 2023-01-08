<?php

function nxs_task_instance_do_send_push_notification_actual_implementation($then_that_item, $taskid, $taskinstanceid)
{
	if ($taskid != 542)
	{
		$result["nackdetails"] = "ERR; you are doing it wrong; send_push_notification_actual_implementation can only be used in task 542, use shortcode send_push_notification shortcode that will create 542 to queue sending of push notifications instead";
		$result["result"] = "NACK";
		return $result;
	}
	
	$marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	$state = $instancemeta["state"];
	
	if ($state == "STARTED")
	{
		// 
		$title = $inputparameters["title"];
		if ($title == "") 
		{
			$result["nackdetails"] = "ERR; $type; $type; title not found in atts of shortcode";
			$result["result"] = "NACK";
			return $result;
		}
		$title = nxs_filter_translatesingle($title, "{{", "}}", $inputparameters);
		
		$message = $inputparameters["message"];
		if ($message == "") 
		{ 
			$result["nackdetails"] = "ERR; $type; message not specified";
			$result["result"] = "NACK";
			return $result;
		}
		$message = nxs_filter_translatesingle($message, "{{", "}}", $inputparameters);
		
		$action_url = "https://global.nexusthemes.com/api/1/prod/send-push-notification/?nxs=pushnotification-api&nxs_json_output_format=prettyprint";
		
		//
		$action_url = nxs_addqueryparametertourl_v2($action_url, "title", $title, true, true);
		$action_url = nxs_addqueryparametertourl_v2($action_url, "message", $message, true, true);
		
		$action_url = nxs_addqueryparametertourl_v2($action_url, "invokedby_taskid", $taskid, true, true);
		$action_url = nxs_addqueryparametertourl_v2($action_url, "invokedby_taskinstanceid", $taskinstanceid, true, true);
		
		$enabler = md5($action_url);
		
		$should_execute = false;
		if (nxs_tasks_isheadless())
		{
			$should_execute = true;
		}
		else
		{
			if ($_REQUEST["doit"] == $enabler)
			{
				$should_execute = true;
			}
		}
		
		if ($should_execute)
		{
			// here we actually send the message
			
			$action_string = file_get_contents($action_url);
			$key = nxs_tasks_getunallocatedinputparameter("send_push_notification_result_json", $inputparameters);
			$result["console"][] = "storing api result in $key";
			nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $key, $action_string);
			$action_result = json_decode($action_string, true);
		}
		else
		{
			$result["console"][] = "procrastinating sending push notification {$title} - {$message}";
			
			$currenturl = nxs_geturlcurrentpage();
	  	$confirm_url = $currenturl;
			$confirm_url = nxs_addqueryparametertourl_v2($confirm_url, "doit", $enabler, true, true);
			$result["console"][] = "<span style='background-color: orange; color: white;'>NOTE: procrastinating</span> the invocation of the API because the shortcode is invoked by the GUI (not headless through a workflow/batch)";
			$result["console"][] = "<a href='{$confirm_url}#$marker'>Click here to send push notification</a>";
		}
	}
	else
	{
		$result["console"][] = "wont do anything here because of state $state";
	}
	
  //
  //
  //
  
  $result["result"] = "OK";
  
  return $result;
}