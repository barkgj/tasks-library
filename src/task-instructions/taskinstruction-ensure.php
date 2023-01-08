<?php

function nxs_task_instance_do_ensure($then_that_item, $taskid, $taskinstanceid)
{
	// $marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$state = $instancemeta["state"];
	
  $result = array();
  
  $should_do_it = true;
  $isheadless = nxs_tasks_isheadless();
  
  if (!in_array($state, array("CREATED", "STARTED", "ENDED", "ABORTED")))
  {
  	nxs_webmethod_return_nack("nxs_task_instance_do_ensure; unsupported state; $state; not sure what to do?");
  }
  
  if ($should_do_it && in_array($state, array("CREATED", "ENDED", "ABORTED")))
  {
  	// dont apply these rules if the task is not started
  	$should_do_it = false;
  }
  
	if ($should_do_it && !$isheadless)
	{
		$should_do_it = false;
	}
	
	
  //
  //
  //
  $issues = array();
  
  // evaluate conditions
  $ensure_no_curly_brackets = $then_that_item["ensure_no_curly_brackets"];
  // apply lookups
  $ensure_no_curly_brackets = nxs_filter_translatesingle($ensure_no_curly_brackets, "{{", "}}", $inputparameters);
  if (isset($ensure_no_curly_brackets))
  {
	  if (nxs_stringcontains($ensure_no_curly_brackets, "{") || nxs_stringcontains($ensure_no_curly_brackets, "}"))
	  {
	  	$issues[]= "nxs_task_instance_do_ensure; violation; still contains a curly bracket";
	  }
	}
	
	// evaluate conditions
  $ensure_curly_brackets = $then_that_item["ensure_curly_brackets"];
  
  if (isset($ensure_curly_brackets))
  {
  	// apply lookups
  	$ensure_curly_brackets = nxs_filter_translatesingle($ensure_curly_brackets, "{{", "}}", $inputparameters);
  
	  if (!nxs_stringcontains($ensure_curly_brackets, "{") || !nxs_stringcontains($ensure_curly_brackets, "}"))
	  {
	  	$issues[]= "nxs_task_instance_do_ensure; violation; should contain a opened and closed curly bracket";
	  }
	}
	
	
	//
	$ensure_true = $then_that_item["ensure_true"];
  // apply lookups
  $ensure_true = nxs_filter_translatesingle($ensure_true, "{{", "}}", $inputparameters);
	if (isset($ensure_true))
	{
		if ($ensure_true != "true")
	  {
	  	$issues[]= "nxs_task_instance_do_ensure; violation; is false";
	  }
	}
	
	$ensure_not_empty = $then_that_item["ensure_not_empty"];
	$ensure_not_empty = nxs_filter_translatesingle($ensure_not_empty, "{{", "}}", $inputparameters);
	
  if (isset($ensure_not_empty))
  {
	  if ($ensure_not_empty == "")
	  {
	  	$issues[]= "nxs_task_instance_do_ensure; violation; ensure_not_empty is empty";
	  }
	}
	
	if (count($issues) > 0)
	{
		$alt_text = $then_that_item["alt_text"];
		
		if ($should_do_it)
		{
			// at least one issue
			$result["nackdetails"] = "nxs_task_instance_do_ensure; at least one violation";
			$result["issues"] = $issues;
			
			if (isset($alt_text))
			{
				$result["alt_text"] = $alt_text;
			}
			
	  	$result["result"] = "NACK";
	  	return $result;
	  }
	  else
	  {
	  	// only report, don't break the GUI
			$result["console"][]= "one or more violations were detected by the ensure instruction;" . json_encode($issues) . "; {$alt_text}";
	  }
	}
	
	// if nothing violates, we are good to go
  $result["result"] = "OK";
  
  return $result;
}