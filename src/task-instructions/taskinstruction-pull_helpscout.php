<?php

function nxs_task_instance_do_pull_helpscout($then_that_item, $taskid, $taskinstanceid)
{
	$result["console"][] = "PULLING HELPSCOUT";
	
	$inputparameters = nxs_task_getinstanceinputparameters($taskid, $taskinstanceid);
	$helpscoutnumber = $inputparameters["original_helpscoutticketnr"];
	
	$interpret_url = "https://global.nexusthemes.com/api/1/prod/interpret-conversation/?nxs=helpscout-api&nxs_json_output_format=prettyprint&helpscoutnumber={$helpscoutnumber}&taskid={$taskid}&taskinstanceid={$taskinstanceid}";
	$interpret_string = file_get_contents($interpret_url);
	$interpret_result = json_decode($interpret_string, true);
	if ($interpret_result["result"] != "OK") { nxs_webmethod_return_nack("unable to fetch interpret_url; $interpret_url"); }

	// store props in task instance as inputparameters
	$inputparameterstoappend = $interpret_result["inputparameterstoappend"];
	if ($inputparameterstoappend != "")
	{
		foreach ($inputparameterstoappend as $key => $val)
		{
			$encodedkey = htmlentities($key);
			$encodedval = htmlentities($val);
			$result["console"][] = "APPENDING INPUTPARAMETER {$encodedkey} {$encodedval}";
		}
		nxs_tasks_appendinputparameters_for_taskinstance($taskid, $taskinstanceid, $inputparameterstoappend);
	}

	$result["console"][] = "PULLED HELPSCOUT";
	$result["result"] = "OK";
	
	return $result;
}