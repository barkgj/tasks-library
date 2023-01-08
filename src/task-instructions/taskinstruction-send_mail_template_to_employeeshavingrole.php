<?php

require_once("/srv/generic/libraries-available/nxs-tasks/task-instructions/taskinstruction-send_mail_template_to_email_address.php");

function nxs_task_instance_do_send_mail_template_to_employeeshavingrole($then_that_item, $taskid, $taskinstanceid)
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
  	$employee_role = $atts["employee_role"];
  	if ($employee_role == "")
  	{
  		$msg = "employee_role not set";
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
  		$fetch_url = "https://global.nexusthemes.com/api/1/prod/search-employees/?nxs=hr-api&nxs_json_output_format=prettyprint&role={$employee_role}";
  		$fetch_string = file_get_contents($fetch_url);
  		$fetch_result = json_decode($fetch_string, true);
  		if ($fetch_result["result"] != "OK")
  		{
  			$msg = "error fetching employees";
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
  		if ($fetch_result["found"] == false)
  		{
  			$msg = "no employees found";
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
  		
  		$index = -1;
  		foreach ($fetch_result["employees"] as $props)
  		{
  			$index++;
  			
	  		$firstname = $props["firstname"];
	  		$toemail = $props["email"];
	
				$delegated_then_that_item = $atts;
				$delegated_then_that_item["firstname"] = $firstname;
				$delegated_then_that_item["toemail"] = $toemail;
				
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
		  	$result["delegated_result_{$index}"] = $delegated_result;
		  }
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