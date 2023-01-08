<?php

require_once("/srv/generic/libraries-available/nxs-tasks/task-instructions/taskinstruction-send_mail_template_to_email_address.php");

function nxs_task_instance_do_send_mail_template_to_license_owner($then_that_item, $taskid, $taskinstanceid)
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
  	$licenseid = $atts["licenseid"];
  	if ($licenseid == "")
  	{
  		$msg = "licenseid not set";
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
  		// derive firstname and e-mail from license
  		
  		$licenseinsights_url = "https://license1802.nexusthemes.com/api/1/prod/licenseinsights/?nxs=licensemeta-api&nxs_json_output_format=prettyprint&licensenr={$licenseid}";
			$licenseinsights_string = file_get_contents($licenseinsights_url);
			$licenseinsights_result = json_decode($licenseinsights_string, true);
			if ($licenseinsights_result["result"] != "OK") 
			{ 
				$msg = "ERR; $type; invalid license? (unable to fetch licenseinsights_url; $licenseinsights_url)";
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
			
			// firstname
  		$firstname = $licenseinsights_result["license_data"]["clientmeta"]["billing_first_name"];
  		if ($firstname == "")
  		{
  			$firstname = $licenseinsights_result["license_data"]["billing_first_name"];
  		}
  		
  		// firstname
  		$toemail = $licenseinsights_result["billing_email"];
  		if ($toemail == "")
  		{
  			$toemail = $licenseinsights_result["license_data"]["billing_email"];
  			if ($toemail == "")
  			{
  				$toemail = $licenseinsights_result["license_data"]["clientmeta"]["billing_email"];
  			}
  		}
  		
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