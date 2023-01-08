<?php

function nxs_task_instance_do_send_push_notification_async($then_that_item, $taskid, $taskinstanceid)
{
	if ($taskid == 542)
	{
		$result["nackdetails"] = "ERR; you are doing it wrong; send_push_notification_async can only be used outside task 542, else an endless loop would be created";
		$result["result"] = "NACK";
		return $result;
	}
	
	//
	$atts = $then_that_item;
	
	$marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	// replace placeholders in values of atts
	foreach ($atts as $key => $val)
	{
		if (nxs_stringcontains($val, "{{") && nxs_stringcontains($val, "}}"))
		{
			$atts[$key] = nxs_filter_translatesingle($val, "{{", "}}", $inputparameters);
		}
	}
	
	
	$state = $instancemeta["state"];
	
	if ($state == "STARTED")
	{
		// 
		$title = $atts["title"];
		if ($title == "") 
		{
			$result["nackdetails"] = "ERR; $type; $type; title not found in atts of shortcode";
			$result["result"] = "NACK";
			return $result;
		}
		$title = nxs_filter_translatesingle($title, "{{", "}}", $inputparameters);
		
		$message = $atts["message"];
		if ($message == "") 
		{ 
			$result["nackdetails"] = "ERR; $type; message not specified";
			$result["result"] = "NACK";
			return $result;
		}
		$message = nxs_filter_translatesingle($message, "{{", "}}", $inputparameters);
		$enabler = md5($title . "-" . $message);
		
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
			$taskid_to_create = 542;
			$assigned_to = "";
			$createdby_taskid = $taskid;
			$createdby_taskinstanceid = $taskinstanceid;
			$mail_assignee = false;
		
			$result["console"][] = "creating task {$taskid_to_create} to send push notification (headless)";
			$create_result = nxs_tasks_createtaskinstance($taskid_to_create, $assigned_to, $createdby_taskid, $createdby_taskinstanceid, $mail_assignee, $atts);
			if ($create_result["result"] != "OK")
			{
				$msg = "unable to send push notification (headless); task creation failed";
				if (nxs_tasks_isheadless())
				{
					error_log($msg);
					$result["nackdetails"] = $msg;
			  	$result["result"] = "NACK";
			  	return $result;
				}
				else
				{
					$result["console"][] = "<span style='background-color: red; color: white;'>ERR; Unable to send push notification; {$msg}; {$title} - {$message}</span>";
					$result["result"] = "OK";
					return $result;
				}
			}
		}
		else
		{
			$result["console"][] = "procrastinating creating task to send push notification {$title} - {$message}";
			
			$currenturl = nxs_geturlcurrentpage();
	  	$confirm_url = $currenturl;
			$confirm_url = nxs_addqueryparametertourl_v2($confirm_url, "doit", $enabler, true, true);
			$result["console"][] = "<span style='background-color: orange; color: white;'>NOTE: procrastinating</span> the invocation of the API because the shortcode is invoked by the GUI (not headless through a workflow/batch)";
			$result["console"][] = "<a href='{$confirm_url}#$marker'>Click here to create task to actually send push notification</a>";
		}
	}
	else
	{
		$result["console"][] = "wont do anything here because of state $state ({$title} - {$message})";
	}
	
  //
  //
  //
  
  $result["result"] = "OK";
  
  return $result;
}