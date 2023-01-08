<?php

function nxs_task_instance_do_end_task_instance($then_that_item, $taskid, $taskinstanceid)
{
	$instance = nxs_task_getinstance($taskid, $taskinstanceid);
	if ($instance["isfound"] == false)
	{
		$result["result"] = "OK";
		$result["console"][] = "unable to render end task instance; instance not found (probably archived)";
		return $result;
	}
	
	$state = $instance["state"];
	
	if (false)
	{
		//
	}
	else if ($state == "ENDED")
	{
		$result["console"][] = "END TASK INSTANCE NOT YET POSSIBLE (ALREADY ENDED)";
	}
	else if ($state == "ABORTED")
	{
		$result["console"][] = "END TASK INSTANCE NOT YET POSSIBLE (ITS ABORTED)";
	}
	else if ($state == "SLEEPING")
	{
		$result["console"][] = "END TASK INSTANCE NOT YET POSSIBLE (ITS SLEEPING)";
	}
	else if ($state == "CREATED" || $state == "STARTED")
	{
		$action_url = "https://global.nexusthemes.com/api/1/prod/end-task-instance/?nxs=businessprocess-api&nxs_json_output_format=prettyprint&businessprocesstaskid={$taskid}&instance_context={$taskinstanceid}";
	
		$isheadless = nxs_tasks_isheadless();
		if ($isheadless)
		{
			$result["console"][] = "ENDING TASK INSTANCE";
			
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
		
			$result["console"][] = "TASK INSTANCE ({$taskid})({$taskinstanceid}) ENDED";
		}
		else
		{
			$note_reference = "";
			$note = "";
			if (nxs_task_willtriggerwakeparentwhenclosingtaskinstance($taskid, $taskinstanceid))
			{
				$note_reference = "&#42;1";
				$note = "<div style='display: inline-block; font-size: 70%; font-style: italic;'>{$note_reference}; this will WAKE the parent task instance</div>";
			}
			else
			{
				$note_reference = "&#42;1";
				$note = "<div style='display: inline-block; font-size: 70%; font-style: italic;'>{$note_reference}; this will NOT wake the parent task instance</div>";
			}
			
			if ($note != "")
			{
				$note = "<br />{$note}";
			}

			$result["console"][] = "<iframe style='display: none; width: 100%; height: 100px;'></iframe><a href='#' onclick=\"jQuery(this).closest('.taskinstruction').css('opacity', '0.2').find('iframe').show().attr('src', '{$action_url}'); return false;\">end task instance {$note_reference}</a>{$note}";
		}
	}
	else
	{
		$result = array
		(
			"result" => "NACK",
			"details" => "unsupported state; $state",
		);
		return $result;
	}
	
	$result["result"] = "OK";
	
	return $result;
}