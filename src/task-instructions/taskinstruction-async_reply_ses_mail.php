<?php

function nxs_task_instance_do_async_reply_ses_mail($then_that_item, $taskid, $taskinstanceid)
{
	$inputparameters = nxs_tasks_gettaskinstancelookup($taskid, $taskinstanceid);
	
	if ($inputparameters["async_reply"] == "sent")
	{
		$result["console"][] = "DONE; Async reply ses mail task instance was already created";
		$result["result"] = "OK";
		return $result;
	}
	
	if ($inputparameters["custom_reply"] == "")
	{
		$result["console"][] = "Unable to async send mail; custom_reply not yet set";
		$result["result"] = "OK";
		return $result;
	}
	if ($inputparameters["sender_email"] == "")
	{
		$result["console"][] = "Unable to async send mail; sender_email not yet set";
		$result["result"] = "OK";
		return $result;
	}
	if ($inputparameters["event_Records_0_ses_mail_commonHeaders_messageId"] == "")
	{
		$result["console"][] = "Unable to async send mail; event_Records_0_ses_mail_commonHeaders_messageId not yet set (no context?)";
		$result["result"] = "OK";
		return $result;
	}
	
	
	
	
	$do_async_reply_ses_mail_action = $_REQUEST["do_async_reply_ses_mail_action"];
	if ($do_async_reply_ses_mail_action == "true")
	{
		
		// ensure we are not double sending
		if ($inputparameters["async_reply"] == "sent")
		{
			echo "unable to continu; already sent? (see child instance of 33). If you want to explicitly resend, then set async_reply inputparameter to something other than true";
			die();
		}
		// set field to avoid double sending email
		nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, "async_reply", "sent");
		
		// create task 33
		$createtaskid = 33;
				
		$inputparametersnewinstance = array
		(
			"toemail" => $inputparameters["sender_email"],
			"mailtemplate" => "234",
			"subject" => $inputparameters["subject_original_ticket"],
			"body" => $inputparameters["custom_reply"],
		);
		$r = nxs_tasks_createtaskinstance($createtaskid, "", $taskid, $taskinstanceid, "", $inputparametersnewinstance);
		$newtaskinstanceid = $r["taskinstanceid"];
		
		$result["console"][] = "DONE; Async reply ses mail task instance {$newtaskinstanceid} created (33)";
		
		$result["result"] = "OK";
		
		return $result;
	}
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = nxs_tasks_gettaskinstancelookup($taskid, $taskinstanceid);
	
	$button_text = $then_that_item["button_text"];
	if ($button_text == "")
	{
		$button_text = "Async send mail";
	}
	
	$result["console"][] = "Async reply ses mail";
	
	$html = "";
	$url = nxs_geturlcurrentpage();
	$html .= "<form action='{$url}' method='POST'>";
	$html .= "<input type='hidden' name='do_async_reply_ses_mail_action' value='true' />";
	$html .= "<input type='submit' value='Send async mail (will create task 33)' />";
	$html .= "</form>";

	// remove noise from template, BEFORE putting in parameters
	$html = str_replace("\r", "", $html);
	$html = str_replace("\n", "", $html);
	
	$result["console"][] = $html;
	$result["console"][] = "<span id='{$marker_id}'></span>";
		
	$result["result"] = "OK";
	
	return $result;
}