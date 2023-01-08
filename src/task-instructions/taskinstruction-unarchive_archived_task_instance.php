<?php

require_once("/srv/generic/libraries-available/nxs-tasks/nxs-tasks.php");

function nxs_task_instance_do_unarchive_archived_task_instance($then_that_item, $taskid, $taskinstanceid)
{
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$state = $instancemeta["state"];
	if ($state != "STARTED")
	{
		$result["result"] = "OK";
  	$result["console"][] = "ignoring instruction as task is not started ($state)";
  	return $result;
	}
	
	$inputparameters = $instancemeta["inputparameters"];
	
  $result = array();
  
  $taskid_to_unarchive = $inputparameters["taskid_to_unarchive"];
  if ($taskid_to_unarchive == "")
  {
  	$result["result"] = "NACK";
  	$result["nack_details"] = "taskid_to_unarchive not set";
  	return $result;
  }
  
  $taskinstanceid_to_unarchive = $inputparameters["taskinstanceid_to_unarchive"];
  if ($taskinstanceid_to_unarchive == "")
  {
  	$result["result"] = "NACK";
  	$result["nack_details"] = "taskinstanceid_to_unarchive not set";
  	return $result;
  }
  
  $check_instancemeta = nxs_task_getinstance($taskid_to_unarchive, $taskinstanceid_to_unarchive);
  if ($check_instancemeta["isfound"] == false)
  {
	  $result["console"][] = "REQUESTED ITEM ($taskid_to_unarchive, $taskinstanceid_to_unarchive) NOT FOUND IN LIVE DATA";
	  
	  $result["console"][] = "LOCATING ITEM IN ARCHIVES ...";
	  
	  $result["console"][] = "LOCATING ITEM IN ARCHIVES ...";
	  
	  $findarchivedinstanceresult = nxs_tasks_archive_findarchivedinstance($taskid_to_unarchive, $taskinstanceid_to_unarchive);
	  if ($findarchivedinstanceresult["isfound"])
	  {
	  	$result["console"][] = "FOUND IN ARCHIVE";
	  	
	  	$unarchive_result = nxs_task_unarchive_archived_task_instance($taskid_to_unarchive, $taskinstanceid_to_unarchive);
	  	
	  	$result["console"][] = "UNARCHIVE RESULT; " . json_encode($unarchive_result);
		}
		else
		{
	  	$result["console"][] = "NOT FOUND IN ARCHIVE";
	  }
  }
  else
  {
  	$result["console"][] = "ERR UNARCHIVING; ($taskid_to_unarchive, $taskinstanceid_to_unarchive) IT ALREADY EXISTS";
  }
  
  $result["result"] = "OK";
  
  return $result;
}