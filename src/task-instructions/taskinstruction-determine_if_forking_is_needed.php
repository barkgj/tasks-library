<?php

function nxs_task_instance_do_determine_if_forking_is_needed($then_that_item, $taskid, $taskinstanceid)
{
	$result["console"][] = "DETERMINE IF WORKING IS NEEDED";
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$field = "fork_required_conclusion";
	
	// TODO: add smartness in here that detects if forking is needed,
	// for example by counting the number of lines of input,
	// or by counting the number of question marks,
	// or the use of words like "another", or "questions"
	// which logic will be added here should be dependent upon the cases
	// identified while handling 131 (131 - Qualify message from customer/prospect)
	
	$value = "NOT_REQUIRED";
	nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $field, $value);

	$result["console"][] = "STORED VALUE $value as input parameter $field";
	$result["result"] = "OK";
	
	return $result;
}