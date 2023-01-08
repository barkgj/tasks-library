<?php

function nxs_task_instance_do_set_inputparameter($then_that_item, $taskid, $taskinstanceid)
{
	$result["console"][] = "SET INPUT PARAMETER TO VALUE";
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$key = $then_that_item["key"];
	$value = $then_that_item["value"];
	
	nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $key, $value);

	$result["console"][] = "SET VALUE OF $key TO $value";
	$result["result"] = "OK";
	
	return $result;
}