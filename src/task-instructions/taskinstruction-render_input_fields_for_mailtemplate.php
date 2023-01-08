<?php

require_once("/srv/generic/libraries-available/nxs-mail/nxs_mail_logic.php");

function nxs_task_instance_do_render_input_fields_for_mailtemplate($then_that_item, $taskid, $taskinstanceid)
{
	if ($taskid != 33)
	{
		$result["nackdetails"] = "ERR; you are doing it wrong; send_mail_template_to_email_address_actual_implementation can only be used in task 33, use a different shortcode that will create 33 to queue sending of emails through task 33 instead";
		$result["result"] = "NACK";
		return $result;
	}

	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	$state = $instancemeta["state"];
	
	if ($state == "STARTED")
	{
		if (nxs_tasks_isheadless())
		{
			$result["console"][] = "nothing to do for headless user";
		}
		else
		{
			$mailtemplate = $inputparameters["mailtemplate"];
			if ($mailtemplate == "") 
			{ 
				$result["nackdetails"] = "ERR; $type; mailtemplate not specified";
				$result["result"] = "NACK";
				return $result;
			}
			
			$placeholders = nxs_mail_getmailtemplateplaceholders($mailtemplate);
			$parameters = $placeholders["combined"];
			$count = count($parameters);
			$result["console"][] = "Mailtemplate {{mailtemplate}} uses {$count} variables";
			foreach ($parameters as $parameter)
			{
				$result["console"][] = do_shortcode("* [nxs_p001_task_instruction type='input-parameter' name='{$parameter}' inputtype='text']");
			}
		}
	}
	else
	{
		$result["console"][] = "wont do anything here because of state $state";
	}
	
  //
  //
  //
  
  $result["result"] = "OK";
  
  return $result;
}