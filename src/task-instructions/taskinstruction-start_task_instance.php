<?php

function nxs_task_instance_do_start_task_instance($then_that_item, $taskid, $taskinstanceid)
{
	$instance = nxs_task_getinstance($taskid, $taskinstanceid);
	$state = $instance["state"];

	
	if (false)
	{
	}
	else if ($state == "CREATED")
	{
		$result["console"][] = "STARTING TASK INSTANCE";
	
		$assignedtoemployee_id = nxs_task_gui_getemployeeidcurrentuser();
		$action_url = "https://global.nexusthemes.com/api/1/prod/start-task-instance/?nxs=businessprocess-api&nxs_json_output_format=prettyprint&businessprocesstaskid={$taskid}&instance_context={$taskinstanceid}&assignedtoemployee_id={$assignedtoemployee_id}";

		$should_do_it = false;
		$isheadless = nxs_tasks_isheadless();
		if ($isheadless)
		{
			$should_do_it = true;
		}
		else
		{
			if ($_REQUEST["autostart"] == "true")
			{
				$should_do_it = true;
			}
			else
			{
				$assignedtoemployee_id = nxs_task_gui_getemployeeidcurrentuser();
				$action_url = "https://global.nexusthemes.com/api/1/prod/start-task-instance/?nxs=businessprocess-api&nxs_json_output_format=prettyprint&businessprocesstaskid={$taskid}&instance_context={$taskinstanceid}&assignedtoemployee_id={$assignedtoemployee_id}";
				
				$lines = array
				(
					"START TASK INSTANCE",
					"----------------------------------------------------------------------------------------------------",
					"<iframe style='display: none; width: 100%; height: 100px;'></iframe>",
					"* <a href='#' style='background-color: green; color: white;' onclick=\"jQuery(this).closest('.taskinstruction').css('opacity', '0.2').find('iframe').show().attr('src', '{$action_url}'); return false;\">CLICK HERE TO START TASK INSTANCE</a>",
				);
				
				foreach ($lines as $line)
				{
					$line = do_shortcode($line);
					$result["console"][] = "{$line}\r\n";
				}
			}
		}
	
		if ($should_do_it)
		{
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
			
			$result["console"][] = "TASK INSTANCE STARTED";
			
			if ($_REQUEST["autostart"] == "true")
			{
				// reload page
				$marker = $then_that_item["marker"];
				$random_reload_gui_token = time();
			  $currenturl = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={$taskid}&taskinstanceid={$taskinstanceid}&random_reload_gui_token={$random_reload_gui_token}#{$marker}";
			  $result["console"][] = "<script>window.location.href = '{$currenturl}';</script>";
			  $result["console"][] = "About to redirect ... ";
			}
		}
		else
		{
			//
		}
	}
	
	$result["result"] = "OK";
	
	return $result;
}