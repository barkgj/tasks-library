<?php

function nxs_task_instance_do_translate_inputparameter_to_inputparameter($then_that_item, $taskid, $taskinstanceid)
{
	$result["console"][] = "TRANSLATING INPUT PARAMETER TO INPUT PARAMETER";
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$source_name = $then_that_item["source_name"];
	$value = $inputparameters[$source_name];
	
	$translate_functions = $then_that_item["translate_functions"];
	foreach ($translate_functions as $translate_function)
	{
		$translate_function_type = $translate_function["type"];
		if (false)
		{
		}
		else if ($translate_function_type == "get_value_between")
		{
			$start = $translate_function["start"];
			$end = $translate_function["end"];
			
			$value = nxs_string_getbetween($value, $start, $end);
		}
		else if ($translate_function_type == "get_value_after")
		{
			$seperator = $translate_function["seperator"];
			$pieces = explode($seperator, $value, 2);
			$value = $pieces[1];
		}
		else if ($translate_function_type == "strip_tags")
		{
			$value = strip_tags($value);
		}
		else if ($translate_function_type == "trim")
		{
			$value = trim($value);
		}
		else
		{
			$result = array
			(
				"result" => "NACK",
				"details" => "unsupported translate_function_type; $translate_function_type",
			);
			return $result;
		}
	}
	
	// 
	$destination_name = $then_that_item["destination_name"];
	nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $destination_name, $value);

	$result["console"][] = "STORED VALUE $value as input parameter $destination_name";
	$result["result"] = "OK";
	
	return $result;
}