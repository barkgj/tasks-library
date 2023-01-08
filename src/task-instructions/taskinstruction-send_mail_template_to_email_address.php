<?php

function nxs_task_instance_do_send_mail_template_to_email_address($then_that_item, $taskid, $taskinstanceid)
{
	// $marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	// runtime parameters
	$inputparameters["taskid"] = $taskid;
	$inputparameters["taskinstanceid"] = $taskinstanceid;
	
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
  	// check: input parameters should not have any placeholders
  	foreach ($atts as $key => $val)
		{
			if (nxs_stringcontains($val, "{{") || nxs_stringcontains($val, "}}"))
			{
				$msg = "unable to send send-mail-template-to-email-address; parameter {$key} contains an unreplaced place holder (contains {{ or }})";
				if (nxs_tasks_isheadless())
				{
					error_log($msg);
					$result["nackdetails"] = $msg;
			  	$result["result"] = "NACK";
			  	return $result;
				}
				else
				{
					$result["console"][] = "<span style='background-color: red; color: white;'>ERR; Unable to send mail; {$msg}</span>";
					$result["result"] = "OK";
					return $result;
				}
			}
		}
  	
  	error_log("mail;1");
  	
  	$mailtemplate = $atts["mailtemplate"];
  	if ($mailtemplate == "")
  	{
  		$msg = "unable to send send-mail-template-to-email-address (mailtemplate {$mailtemplate}; mailtemplate not specified";
			if (nxs_tasks_isheadless())
			{
				error_log($msg);
				$result["nackdetails"] = $msg;
		  	$result["result"] = "NACK";
		  	return $result;
			}
			else
			{
				$result["console"][] = "<span style='background-color: red; color: white;'>ERR; Unable to send mail; {$msg}</span>";
				$result["result"] = "OK";
				return $result;
			}
  	}
  	
  	error_log("mail;2");
  	
		$taskid_to_create = 33;
		$whatmakesthisunique = "";
		$whatmakesthisunique .= "{$taskid_to_create};";
  	
		require_once("/srv/generic/libraries-available/nxs-mail/nxs_mail_logic.php");
		$placeholders_result = nxs_mail_getmailtemplateplaceholders($mailtemplate);
		$requiredplaceholders = $placeholders_result["combined"];
		foreach ($requiredplaceholders as $requiredplaceholder)
		{
			if (!isset($atts[$requiredplaceholder]))
			{
				$msg = "unable to send send-mail-template-to-email-address (mailtemplate {$mailtemplate}; required field $requiredplaceholder in the mailtemplate is not set";
				if (nxs_tasks_isheadless())
				{
					error_log($msg);
					$result["nackdetails"] = $msg;
			  	$result["result"] = "NACK";
			  	return $result;
				}
				else
				{
					$result["console"][] = "<span style='background-color: red; color: white;'>ERR; Unable to send mail; {$msg}</span>";
					$result["result"] = "OK";
					return $result;
				}
			}
			else
			{
				$val = $atts[$requiredplaceholder];
				$notallowedlist = array("{", "}", "'", "]", "[");
				foreach ($notallowedlist as $notallowed)
				{
					// error_log("err; $requiredplaceholder; " . $val);
					if (nxs_stringcontains($val, $notallowed))
					{
						$msg = "err... required field $requiredplaceholder in the mailtemplate contains invalid char; (( $notallowed )) (( $val ))";
						if (nxs_tasks_isheadless())
						{
							error_log($msg);
							$result["nackdetails"] = $msg;
					  	$result["result"] = "NACK";
					  	return $result;
						}
						else
						{
							$result["console"][] = "<span style='background-color: red; color: white;'>ERR; Unable to send mail; {$msg}</span>";
							$result["console"][] = "atts: " . json_encode($atts);
							$result["result"] = "OK";
							return $result;
						}
					}
				}
				
				$whatmakesthisunique .= "{$requiredplaceholder}={$val};";
			}
		}
		
		error_log("mail;3");
		
		$assigned_to = "";
		$createdby_taskid = $taskid;
		$createdby_taskinstanceid = $taskinstanceid;
		$mail_assignee = false;

		$enabler = md5($whatmakesthisunique);

		$doit = false;
		if (nxs_tasks_isheadless())
		{
			$doit = true;
			
		}		
		else
		{
			$doit = false;
	  	if ($_REQUEST["doit"] == $enabler)
	  	{
	  		$doit = true;
	  	}
		}
		
		if ($doit)
		{
			$result["console"][] = "creating task 33 to send mail (headless)";
			$create_result = nxs_tasks_createtaskinstance($taskid_to_create, $assigned_to, $createdby_taskid, $createdby_taskinstanceid, $mail_assignee, $atts);
			if ($create_result["result"] != "OK")
			{
				$msg = "unable to send send-mail-template-to-email-address (mailtemplate {$mailtemplate}; task creation 33 failed";
				if (nxs_tasks_isheadless())
				{
					error_log($msg);
					$result["nackdetails"] = $msg;
			  	$result["result"] = "NACK";
			  	return $result;
				}
				else
				{
					$result["console"][] = "<span style='background-color: red; color: white;'>ERR; Unable to send mail; {$msg}</span>";
					$result["result"] = "OK";
					return $result;
				}
			}
			else
			{
				$marker = $then_that_item["marker"];
				$random_reload_gui_token = time();
			  $currenturl = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={$taskid}&taskinstanceid={$taskinstanceid}&random_reload_gui_token={$random_reload_gui_token}#{$marker}";
			  $result["console"][] = "<script>window.location.href = '{$currenturl}';</script>";
			  $result["console"][] = "About to redirect ... ";
			}
		}
		else
		{
			// ---
			$previewmailurl = "https://global.nexusthemes.com/?nxs=task-gui&page=mailtemplatepreview&mailtemplateid={$mailtemplate}";
				
			$exclude_keys = array("type", "helpscout_conversation_nr", "mailtemplate");
			foreach ($atts as $key => $val)
			{
				if (in_array($key, $exclude_keys))	
				{
					// skip
					continue;
				}
				
				// replace placeholders if any are remaining
				$val = nxs_filter_translatesingle($val, "{{", "}}", $inputparameters);
			
				$action_url = nxs_addqueryparametertourl_v2($action_url, $key, $val, true, true);
				$previewmailurl  = nxs_addqueryparametertourl_v2($previewmailurl, $key, $val, true, true);
			}			
			
			$taskid = $_REQUEST["taskid"];
			$taskinstanceid = $_REQUEST["taskinstanceid"];

			$previewmailurl = nxs_addqueryparametertourl_v2($previewmailurl, "invokedby_taskid", $taskid, true, true);
			$previewmailurl = nxs_addqueryparametertourl_v2($previewmailurl, "invokedby_taskinstanceid", $taskinstanceid, true, true);
			$previewmailurl = nxs_addqueryparametertourl_v2($previewmailurl, "taskid", $taskid, true, true);
			$previewmailurl = nxs_addqueryparametertourl_v2($previewmailurl, "taskinstanceid", $taskinstanceid, true, true);
			
			
			if (false) // $mailtemplate == 57)
			{
				$question_id = $atts["question_id"];
				$previewmailurl = "https://nexusthemes.com/aap-1029/mies-{$question_id}/?taskid={$taskid}&taskinstanceid={$taskinstanceid}&preview=true";
				//$improveurl = "https://docs.google.com/spreadsheets/d/1DMUBnvJTlRvmsKR-FQcxj3Km5ehXKvLmZLETSy6HDrk/edit?usp=drive_web&ouid=101834797161834314384";
			}
			else
			{

			}
			
			// ---
			
			$currenturl = nxs_geturlcurrentpage();
	  	$action_url = $currenturl;
			$action_url = nxs_addqueryparametertourl($action_url, "doit", $enabler, true, true);
			$result["console"][] = "<span style='background-color: orange; color: white;'>NOTE: procrastinating</span> the creation of 33 instance to send the mailtemplate {$mailtemplate} because the shortcode is invoked by the GUI (not headless through a workflow/batch)";
			
			$result["console"][] = "<a target='_blank' href='{$previewmailurl}'>Click here to preview the email that will be send</a>";
			$result["console"][] = "<a href='{$action_url}#$marker'>Click here to create 33 instance to send email</a>";
		}
		
		error_log("mail;4");
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