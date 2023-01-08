<?php

function nxs_task_instance_do_ensure_no_matching_taskinstances($then_that_item, $taskid, $taskinstanceid)
{
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$state = $instancemeta["state"];
	
	$result = array();
	
	if ($state == "STARTED")
	{
		$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
		$inputparameters = $instancemeta["inputparameters"];
	
	  // construct the search arguments
	  if (true)
	  {
		  $subconditions = array();
		  
		  $f_states = $then_that_item["f_states"];
		  if ($f_states != "")
		  {
		  	$subconditions[] = array
				(
					"type" => "true_if_in_any_of_the_required_states",
					"any_of_the_required_states" => explode("|", $f_states),
				);
		  }
		  
			$f_taskid = $then_that_item["f_taskid"];
		  if ($f_taskid != "")
		  {
		  	$subconditions[] = array
				(
					"type" => "true_if_task_has_required_taskid",
					"required_taskid" => $f_taskid
				);
		  }
		  
		  // f1
		  $f1_key = $then_that_item["f1_key"];
		  $f1_op = $then_that_item["f1_op"];
		  $f1_equals = $then_that_item["f1_equals"];
		  $f1_equals = nxs_filter_translatesingle($f1_equals, "{{", "}}", $inputparameters);
		  if ($f1_key != "")
		  {
		  	if ($f1_op == "equals")
		  	{
		  		$subconditions[] = array
					(
						"type" => "true_if_inputparameter_has_required_value_for_key",
						"key" => $f1_key,
						"required_value" => $f1_equals
					);
		  	}
		  	else
		  	{
		  		// not supported
		  	}
		  	
		  }
		  
		  // f2
		  $f2_key = $then_that_item["f2_key"];
		  $f2_op = $then_that_item["f2_op"];
		  $f2_equals = $then_that_item["f2_equals"];
		  $f2_equals = nxs_filter_translatesingle($f2_equals, "{{", "}}", $inputparameters);
		  if ($f2_key != "")
		  {
		  	if (false)
		  	{
		  	}
		  	else if ($f2_op == "equals")
		  	{
		  		$subconditions[] = array
					(
						"type" => "true_if_inputparameter_has_required_value_for_key",
						"key" => $f2_key,
						"required_value" => $f2_equals
					);
		  	}
		  	else if ($f2_op == "!equals")
		  	{
		  		$subconditions[] = array
					(
						"type" => "true_if_inputparameter_not_has_required_value_for_key",
						"key" => $f2_key,
						"value" => $f2_equals
					);
		  	}
		  	else
		  	{
		  		// not supported
		  	}
		  	
		  }
		  
		  $search_args = array
			(
				"if_this" => array
				(
					"type" => "true_if_each_subcondition_is_true",
					"subconditions" => $subconditions,
				),
			);
		}
		$taskinstances_wrap = nxs_tasks_searchtaskinstances($search_args);
		$taskinstances = $taskinstances_wrap["taskinstances"];
		
		if (count($taskinstances) > 0)
		{
			if (nxs_tasks_isheadless())
			{
				$msg = "unable to proceed; found 1 or more task instances for " . json_encode($search_args);
				$result["console"][] = $msg;
		  	$result["nack_message"] = $msg;
		  	$result["result"] = "NACK";
		  	return $result;
		  }
		  else
		  {
		  	$result["console"][] = "UNABLE TO PROCEED";
		  }
		}
		else
		{
			$result["console"][] = "no matching task instances found (good)";
		}
	}
	else
	{
		$result["console"][] = "<span style='background-color: red; color: white;'>nothing to do because of state: ; found 1 or more task instances for " . json_encode($search_args) . "</span>";
	}
		
  $result["result"] = "OK";
  
  return $result;
}