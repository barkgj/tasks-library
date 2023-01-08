<?php

function nxs_task_instance_do_handle_customer_service_message($then_that_item, $taskid, $taskinstanceid)
{
	$result = array();
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];

	$messageid = $inputparameters["messageid"];
	if ($messageid == "")
	{
		$result["console"][] = "PLEASE SELECT MESSAGEID FIRST";
	}
	else
	{
		$result["console"][] = "YOU SELECTED MESSAGEID; $messageid";
		$schema = "nxs.customerservice.message";
		global $nxs_g_modelmanager;
		$a = array
		(
			"modeluri" => "{$messageid}@{$schema}",
		);
		$props = $nxs_g_modelmanager->getmodeltaxonomyproperties($a);
		
		if ($props["nxs.p001.businessprocess.task_id"] == "")
		{
			// this means its not yet defined how to handle this message
			// we should create a task to handle this (assuming its not yet there)
			$result["console"][] = do_shortcode("* [nxs_p001_task_instruction indent=1 type='create-task-instance' create_taskid=278 render_required_fields=true messageid='{{messageid}}']");	
			$result["console"][] = "* After the task instance is created to Determine course of action for message, refresh the screen and start the determination";
			$result["console"][] = "* After you finish the determination of the course of action for message, refresh the screen to perform the staps according to the determined course of action";
		}
		else
		{
			$taskid_to_create = $props["nxs.p001.businessprocess.task_id"];
			$task_input_parameters = $props["task_input_parameters"];
			$result["console"][] = do_shortcode("* [nxs_p001_task_instruction indent=1 type='create-task-instance' create_taskid={$taskid_to_create} render_required_fields=true {$task_input_parameters} helpscoutthreadid='{{helpscoutthreadid}}' original_helpscoutticketnr='{{original_helpscoutticketnr}}']");
			
			$result["console"][] = do_shortcode("* [nxs_p001_task_instruction indent=1 type='end-task-instance']");
		}
	}
	
	
	$result["result"] = "OK";
	
	return $result;
}