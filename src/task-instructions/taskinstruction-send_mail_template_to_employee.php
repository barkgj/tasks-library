<?php

require_once("/srv/generic/libraries-available/nxs-tasks/task-instructions/taskinstruction-send_mail_template_to_email_address.php");

function nxs_task_instance_do_send_mail_template_to_employee($then_that_item, $taskid, $taskinstanceid)
{
	// $marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	//
	$atts = $then_that_item;
	
	// replace placeholders in values of atts
	foreach ($atts as $key => $val)
	{
		if (nxs_stringcontains($val, "{{") && nxs_stringcontains($val, "}}"))
		{
			$atts[$key] = nxs_filter_translatesingle($val, "{{", "}}", $inputparameters);
		}
	}
	
	$state = $instancemeta["state"];
	
  $result = array();
  
  if ($state == "STARTED")
  {
  	$employee_id = $atts["employee_id"];
  	if ($employee_id == "")
  	{
  		$msg = "employee_id not set";
	    if (nxs_tasks_isheadless())
	    {
	    	$result["result"] = "NACK";
  			$result["nack_details"] = $msg;
	    	return $result;	
	    }
  		else
  		{
  			$result["console"][] = $msg;
  			$result["result"] = "OK";
  			return $result;
  		}
  	}
  	else
  	{
  		// derive firstname and e-mail from specified employee	
  		$modeluri = "{$employee_id}@nxs.hr.employee";
  		global $nxs_g_modelmanager;
			$props = $nxs_g_modelmanager->getmodeltaxonomyproperties(array("modeluri" => $modeluri));
  		$firstname = $props["firstname"];
  		$toemail = $props["email"];

			$delegated_then_that_item = $atts;
			$delegated_then_that_item["firstname"] = $firstname;
			$delegated_then_that_item["toemail"] = $toemail;
			
			/*
			foreach ($delegated_then_that_item as $k => $v)
			{
				$result["console"][] = "item;{$k}:{$v}";
			}
			*/
			
			$delegated_result = nxs_task_instance_do_send_mail_template_to_email_address($delegated_then_that_item, $taskid, $taskinstanceid);
			
			if ($delegated_result["result"] == "NACK")
			{
				$msg = "error sending mail";
		    if (nxs_tasks_isheadless())
		    {
		    	$result["result"] = "NACK";
	  			$result["nack_details"] = $msg;
	  			$result["delegated_result"] = $delegated_result;
		    	return $result;	
		    }
	  		else
	  		{
	  			$result["console"][] = $msg;
	  			$result["result"] = "OK";
	  			$result["delegated_result"] = $delegated_result;
	  			return $result;
	  		}
	  	}

			// copy to the console so we know what happened	  	
	  	foreach ($delegated_result["console"] as $consoleitem)
	  	{
	  		$result["console"][] = $consoleitem;
	  	}
	  	$result["delegated_result"] = $delegated_result;
  	}
  }
  else
  {
  	$result["console"][] = "not sending mail because of state $state";	
  }
  
  //
  //
  //
  
  $result["result"] = "OK";
  
  return $result;
}