<?php

require_once("/srv/generic/libraries-available/nxs-mail/nxs_mail_logic.php");

function nxs_task_instance_do_send_mail_template_to_email_address_actual_implementation($then_that_item, $taskid, $taskinstanceid)
{
	if ($taskid != 33)
	{
		$result["nackdetails"] = "ERR; you are doing it wrong; send_mail_template_to_email_address_actual_implementation can only be used in task 33, use a different shortcode that will create 33 to queue sending of emails through task 33 instead";
		$result["result"] = "NACK";
		return $result;
	}
	
	$marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	$event_Records_0_ses_mail_commonHeaders_messageId = $inputparameters["event_Records_0_ses_mail_commonHeaders_messageId"];
	$state = $instancemeta["state"];
	
	if ($state == "STARTED")
	{
		// 
		$toemail = $inputparameters["toemail"];
		if ($toemail == "") 
		{
			$result["nackdetails"] = "ERR; $type; $type; toemail not found in atts of shortcode";
			$result["result"] = "NACK";
			return $result;
		}
		
		$mailtemplate = $inputparameters["mailtemplate"];
		if ($mailtemplate == "") 
		{ 
			$result["nackdetails"] = "ERR; $type; mailtemplate not specified";
			$result["result"] = "NACK";
			return $result;
		}
		
		$placeholders = nxs_mail_getmailtemplateplaceholders($mailtemplate);
		$parameters = $placeholders["combined"];
		
		$action_url = "https://global.nexusthemes.com/api/1/prod/send-mail-template-actual-implementation/?nxs=mail-api&nxs_json_output_format=prettyprint";
	
		foreach ($parameters as $key)
		{
			if (!isset($inputparameters[$key]))
			{
				if (nxs_tasks_isheadless())
				{
					$result["nackdetails"] = "ERR; unable to proceed; mailtemplate {$mailtemplate} uses parameter $key which is not specified?";
					$result["result"] = "NACK";
					return $result;
				}
				else
				{
					$result["console"][] = "<span style='background-color: red; color: white;'>ERR; unable to proceed; mailtemplate {$mailtemplate} uses parameter $key which is not specified?</span>";
					$result["result"] = "OK";
					return $result;
				}
			}
			
			$val = $inputparameters[$key];
			// replace placeholders if any are remaining
			$val = nxs_filter_translatesingle($val, "{{", "}}", $inputparameters);
			$action_url = nxs_addqueryparametertourl_v2($action_url, $key, $val, true, true);
		}
		
		//
		$action_url = nxs_addqueryparametertourl_v2($action_url, "mailtemplate", $mailtemplate, true, true);
		// $action_url = nxs_addqueryparametertourl_v2($action_url, "tomail", $tomail, true, true);
		
		// add required parameters from taskinstance
		$action_url = nxs_addqueryparametertourl_v2($action_url, "invokedby_taskid", $taskid, true, true);
		$action_url = nxs_addqueryparametertourl_v2($action_url, "invokedby_taskinstanceid", $taskinstanceid, true, true);
		
		if ($event_Records_0_ses_mail_commonHeaders_messageId != "")
		{
			$action_url = nxs_addqueryparametertourl_v2($action_url, "in_reply_to", $event_Records_0_ses_mail_commonHeaders_messageId, true, true);
		}
		
		$enabler = md5($action_url);
		
		$should_execute = false;
		if (nxs_tasks_isheadless())
		{
			$should_execute = true;
		}
		else
		{
			if ($_REQUEST["doit"] == $enabler)
			{
				$should_execute = true;
			}
		}
		
		if ($should_execute)
		{
			// here we actually send the email
			
			$action_string = file_get_contents($action_url);
			
			$key = nxs_tasks_getunallocatedinputparameter("send_mail_template_result_json_{$mailtemplate}", $inputparameters);
			$result["console"][] = "storing api result in $key";
			nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $key, $action_string);
			
			$action_result = json_decode($action_string, true);
			
			if ($action_result["result"] == "OK" && $action_result["mailresult"]["wp_mail_result"] == true)
			{
				// ok
				$result["console"][] = "mail sending api returned OK";
			}
			else
			{
				$result["nackdetails"] = "ERR; unable to send mail, see $key";
				$result["result"] = "NACK";
				return $result;
			}
		}
		else
		{
			$result["console"][] = "procrastinating sending email template {$mailtemplate}";
			$result["console"][] = "parameters used by the mailtemplate:";
			foreach ($parameters as $parameter)
			{
				$result["console"][] = "parameter: $parameter";
			}
			
			$currenturl = nxs_geturlcurrentpage();
	  	$confirm_url = $currenturl;
			$confirm_url = nxs_addqueryparametertourl_v2($confirm_url, "doit", $enabler, true, true);
			$result["console"][] = "<span style='background-color: orange; color: white;'>NOTE: procrastinating</span> the invocation of the API because the shortcode is invoked by the GUI (not headless through a workflow/batch)";
			$result["console"][] = "<a href='{$confirm_url}#$marker'>Click here to send mail</a>";
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