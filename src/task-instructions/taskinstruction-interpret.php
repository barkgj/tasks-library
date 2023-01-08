<?php

function nxs_task_instance_do_interpret($then_that_item, $taskid, $taskinstanceid)
{
	//$result["console"][] = "INTERPRET";
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = nxs_tasks_gettaskinstancelookup($taskid, $taskinstanceid);
	
	// then_that_item keyvalues are stronger than inputparameters
	$args = wp_parse_args($then_that_item, $inputparameters);
	
	// validations
	foreach ($then_that_item as $then_that_key => $then_that_value)
	{
		if ($then_that_value == "{{{$then_that_key}}}")
		{
			$result["console"][] = "interpret; ERR; you are doing it wrong; attributes ({then_that_key}) are not allowed to get a value with the same placeholder name ({{then_that_key}}); to fix this, prefix the value to {{i_{$then_that_key}}} (or any other prefix)";
			$result["result"] = "NACK";
			return $result;
		}
	}	
	
	if (true)
	{
		$at_least_one_required_att_missing = false;
		$required_atts = array
		(
			"interpretation_attachmentid"
		);
		foreach ($required_atts as $required_att)
		{
			if ($args[$required_att] == "")
			{
				$result["console"][] = "interpret; ERR; {$required_att} attribute not set in shortcode";
				$at_least_one_required_att_missing = true;
			}	
		}
		
		if ($at_least_one_required_att_missing)
		{
			$result["result"] = "OK";
			return $result;
		}
	}
	
	if (nxs_tasks_isheadless())
	{
		$interpretation_attachmentid = $args["interpretation_attachmentid"];
		$interpretation_string = nxs_tasks_gettaskrecipe_attachment($taskid, $taskinstanceid, $interpretation_attachmentid);
		$interpretation = json_decode($interpretation_string, true);
		//var_dump($interpretation);
		//die();
		foreach ($interpretation["rules"] as $rule)
		{
			$condition = $rule["condition"];
			$type = $condition["type"];
			if (false)
			{
				//
			}
			else if ($type == "shortcode")
			{
				$shortcode = $condition["shortcode"];
				
			}
			else
			{
				nxs_webmethod_return_nack("interpret;condition;unsupported type $type");
			}
			
			
			
			// apply 
			$result["console"][] = "condition; $condition";	
		}
		//$result["console"][] = "interpretation; $interpretation";
	}
		
	$result["result"] = "OK";
	
	return $result;
}