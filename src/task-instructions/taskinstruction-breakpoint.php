<?php

function nxs_task_instance_do_breakpoint($then_that_item, $taskid, $taskinstanceid)
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
	
	if ($should_do_it)
	{
		$result["alt_text"] = "breakpoint triggered";
		$result["result"] = "NACK";
		return $result;
	}

	// if nothing violates, we are good to go
  $result["result"] = "OK";
  
  return $result;
}