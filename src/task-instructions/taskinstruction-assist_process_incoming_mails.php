<?php

function nxs_task_instance_do_assist_process_incoming_mails($then_that_item, $taskid, $taskinstanceid)
{
	error_log("taskinstruction-assist_process_incoming_mails.php.465534.start");
	
	$result = array();
	
	$active_conversations_url = "https://global.nexusthemes.com/api/1/prod/get-conversations/?nxs=helpscout-api&nxs_json_output_format=prettyprint&state=active";
	$active_conversations_string = file_get_contents($active_conversations_url);
	$active_conversations_result = json_decode($active_conversations_string, true);
	if ($active_conversations_result["result"] != "OK") 
	{
		$result = array
		(
			"result" => "NACK",
			"nack_details" => "unable to fetch active_conversations_url; $active_conversations_url; $active_conversations_string",
		);
		return $result;
	}
	
	foreach ($active_conversations_result["conversations"] as $conversation)
	{
		$number = $conversation["number"];
		$result["console"][] = "Processing helpscout ticket $number";
		
		// invoke the api to create the task instance
		$taskid_to_create = 144;
		$createtaskinstance_url = "https://global.nexusthemes.com/api/1/prod/create-task-instance/?nxs=businessprocess-api&nxs_json_output_format=prettyprint&taskid={$taskid_to_create}&original_helpscoutticketnr={$number}&createdby_taskid={$taskid}&createdby_taskinstanceid={$taskinstanceid}";
		$createtaskinstance_string = file_get_contents($createtaskinstance_url);
		$createtaskinstance_result = json_decode($createtaskinstance_string, true);
		if ($createtaskinstance_result["result"] != "OK") 
		{
			$result = array
			(
				"result" => "NACK",
				"nack_details" => "unable to fetch create task instance; $createtaskinstance_url; $createtaskinstance_string",
			);
			return $result;
		}
		
		$created_taskinstanceid = $createtaskinstance_result["taskinstanceid"];
	
		// add note to the helpscout ticket
		if (true)
		{
			$url = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={$taskid_to_create}&taskinstanceid={$created_taskinstanceid}";
			$note = "is handled by <a href='{$url}'>taskid $taskid_to_create $created_taskinstanceid</a>";
			$addnote_url = "https://global.nexusthemes.com/api/1/prod/add-note-to-helpscout-conversation/?nxs=helpscout-api&nxs_json_output_format=prettyprint";
			$addnote_url = nxs_addqueryparametertourl_v2($addnote_url, "note", $note, true, true);
			$addnote_url = nxs_addqueryparametertourl_v2($addnote_url, "helpscoutnumber", $number, true, true);
			$addnote_string = file_get_contents($addnote_url);
			$addnote_result = json_decode($addnote_string, true);
			if ($addnote_result["result"] != "OK") 
			{
				$result = array
				(
					"result" => "NACK",
					"nack_detail" => "exception adding note; $addnote_url; $addnote_string",
				);
				return $result; 
			}
		}
		
		// close ticket in helpscout
		if (true)
		{
			$closeticket_url = "https://global.nexusthemes.com/api/1/prod/close-ticket-by-number/?nxs=helpscout-api&nxs_json_output_format=prettyprint&helpscoutnumber={$number}";
			$closeticket_string = file_get_contents($closeticket_url);
			$closeticket_result = json_decode($closeticket_string, true);
			if ($closeticket_result["result"] != "OK") 
			{
				$result = array
				(
					"result" => "NACK",
					"nack_details" => "error closing ticket; $closeticket_url; $closeticket_string",
				);
				return $result; 
			}
		}
	}
	
	// 20200624; store the number of pulled conversations to the properties of the task instance
	// allows the orchestrator to create a new instance to pull additionals emails that are likely
	// queued
	error_log("taskinstruction-assist_process_incoming_mails.php.465534:");
	error_log(json_encode($active_conversations_result));
	$count = count($active_conversations_result["conversations"]);
	nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, "count_items_pulled", $count);

	$result["result"] = "OK";
	
	return $result;
}