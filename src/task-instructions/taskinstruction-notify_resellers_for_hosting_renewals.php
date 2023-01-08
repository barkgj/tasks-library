<?php

function nxs_get_remindergroups_for_resellers()
{
	// TODO: probably remindergroups can be stored in ixplatform tables...
	$remindergroups = array();
		
	
	// -----
	$weeks_left = 2;
	$another_reminder = array
	(
		"title" => "1st reminder",
		"requirement_shouldNOTexpireindays" => (7*($weeks_left+0))+1,
		"requirement_hastoexpireindays" => (7*($weeks_left+1)),
		"handled_by_taskid" => 386,
	);
	$remindergroups[] = $another_reminder;
	
	// -----
	$weeks_left = 1;
	$another_reminder = array
	(
		"title" => "2nd reminder",
		"requirement_shouldNOTexpireindays" => (7*($weeks_left+0))+1,
		"requirement_hastoexpireindays" => (7*($weeks_left+1)),
		"handled_by_taskid" => 387,
	);
	$remindergroups[] = $another_reminder;
	
	
	// -----
	$weeks_left = 0;
	$another_reminder = array
	(
		"title" => "last reminder",
		"requirement_shouldNOTexpireindays" => (7*($weeks_left+0))+1,
		"requirement_hastoexpireindays" => (7*($weeks_left+1)),
		"handled_by_taskid" => 388,
	);
	$remindergroups[] = $another_reminder;
	
	//
	return $remindergroups;
}

function nxs_task_instance_do_notify_resellers_for_hosting_renewals($then_that_item, $taskid, $taskinstanceid)
{
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$state = $instancemeta["state"];
	$inputparameters = $instancemeta["inputparameters"];
	
	if ($state == "CREATED")
	{
		$result["console"][] = "skipping; state is created";
		$result["result"] = "OK";
		return $result;
	}
	

	$remindergroups = nxs_get_remindergroups_for_resellers();
	
	$duplicatelicensetracker = array();
	
	foreach ($remindergroups as $remindergroup)
	{
		$title = $remindergroup["title"];
		
		$requirement_shouldNOTexpireindays = $remindergroup["requirement_shouldNOTexpireindays"];
		$requirement_hastoexpireindays = $remindergroup["requirement_hastoexpireindays"];
		$handled_by_taskid = $remindergroup["handled_by_taskid"];
		
		$result["console"][] = "PROCESSING REMINDERGROUP '{$title}'";
		error_log("PROCESSING REMINDERGROUP '{$title}'");
	
		// grab list of resellers
		$resellers_url = "https://global.nexusthemes.com/api/1/prod/get-studios-for-resellers/?nxs=reseller-api&nxs_json_output_format=prettyprint";
		$resellers_string = file_get_contents($resellers_url);
		$resellers_result = json_decode($resellers_string, true);
		if ($resellers_result["result"] != "OK") { nxs_webmethod_return_nack("error fetching studios; $resellers_url"); }
		$studios = $resellers_result["studios"];
		$studios_list = implode("|", $studios);
		
	
		$listlicenses_url = "https://global.nexusthemes.com/api/1/prod/list-licenses/?nxs=license-api&nxs_json_output_format=prettyprint";
		$listlicenses_url = nxs_addqueryparametertourl_v2($listlicenses_url, "requirement_license_type", "hosting", true, true);
		$listlicenses_url = nxs_addqueryparametertourl_v2($listlicenses_url, "requirement_expirationreminder_state", "ENABLED", true, true);
		$listlicenses_url = nxs_addqueryparametertourl_v2($listlicenses_url, "requirement_isrefunded", "false", true, true);
		$listlicenses_url = nxs_addqueryparametertourl_v2($listlicenses_url, "requirement_shouldNOTexpireindays", $requirement_shouldNOTexpireindays, true, true);
		$listlicenses_url = nxs_addqueryparametertourl_v2($listlicenses_url, "requirement_hastoexpireindays", $requirement_hastoexpireindays, true, true);
		$listlicenses_url = nxs_addqueryparametertourl_v2($listlicenses_url, "requirement_in_studio", $studios_list, true, true);
		
		$result["console"][] = "<a target='_blank' href='{$listlicenses_url}'>list licenses url</a>";
		
		$listlicenses_string = file_get_contents($listlicenses_url);
		$listlicenses_result = json_decode($listlicenses_string, true);
		foreach ($listlicenses_result["licenses"] as $license)
		{
			$expirationdate = $license["expirationdate"];
			$licenseid = $license["licensenr"];
			$sitedomain = $license["sitedomain"];
			if ($sitedomain == "")
			{
				// 
				continue;
			}
			$pieces = explode("|", $sitedomain);
			$url = end($pieces);
			$hostname = parse_url($url, PHP_URL_HOST);
			
			// ensure we don't do MULTIPLE reminders to one and the same license
			if (true)
			{
				if (in_array($licenseid, $duplicatelicensetracker))
				{
					nxs_webmethod_return_nack("unexpected; preventing sending multiple notifications to the same license; $licenseid");
				}
				$duplicatelicensetracker[] = $licenseid;
			}
			
			$should_do_it = false;
			if (nxs_tasks_isheadless())
			{
				$should_do_it = true;
			}
			else
			{
				if ($_REQUEST["createinstances"] == "true")
				{
					$should_do_it = true;
				}
			}
			
			if ($should_do_it)
			{
				// create instance
				if (true)
				{
					$createinstance_url = "https://global.nexusthemes.com/api/1/prod/create-task-instance/?nxs=businessprocess-api&nxs_json_output_format=prettyprint";
					$createinstance_url = nxs_addqueryparametertourl_v2($createinstance_url, "taskid", $handled_by_taskid, true, true);
					$createinstance_url = nxs_addqueryparametertourl_v2($createinstance_url, "renew_licenseid", $licenseid, true, true);
					$createinstance_url = nxs_addqueryparametertourl_v2($createinstance_url, "hostname", $hostname, true, true);
					$createinstance_url = nxs_addqueryparametertourl_v2($createinstance_url, "old_expirationdate", $expirationdate, true, true);
					$createinstance_url = nxs_addqueryparametertourl_v2($createinstance_url, "createdby_taskid", $taskid, true, true);
					$createinstance_url = nxs_addqueryparametertourl_v2($createinstance_url, "createdby_taskinstanceid", $taskinstanceid, true, true);
					
					$createinstance_string = file_get_contents($createinstance_url);
					$createinstance_result = json_decode($createinstance_string, true);
					if ($createinstance_result["result"] != "OK") { nxs_webmethod_return_nack("error fetching createinstance_url; $createinstance_url"); }
					$created_taskinstanceid = $createinstance_result["taskinstanceid"];
					$result["console"][] = "created instance {$handled_by_taskid} {$created_taskinstanceid}";
				}
			}
			else
			{
				//
				$result["console"][] = "<span style='background-color: red; color: white;'>WARNING</span>; skipping creating of instance (dry run, use createinstances=true to create the instances); $handled_by_taskid $licenseid $hostname";
			}
		}
		$result["console"][] = "END OF REMINDERGROUP<br />";
	}
	
	$result["result"] = "OK";
	
	return $result;
}