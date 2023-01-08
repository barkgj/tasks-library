<?php

function nxs_task_instance_do_require_license_state($then_that_item, $taskid, $taskinstanceid)
{
	$result["console"][] = "REQUIRE LICENSE STATE";
	$result["console"][] = "-----------";
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$licenseid = $inputparameters["licenseid"];
	if ($licenseid == "")
	{
		$result["result"] = "NACK";
		$result["nack_details"] = "no licenseid found?";
		return $result;
	}
	else
	{
		$fetch_url = "https://license1802.nexusthemes.com/api/1/prod/licenseinsights/?nxs=licensemeta-api&nxs_json_output_format=prettyprint&licensenr={$licenseid}";
		$fetch_string = file_get_contents($fetch_url);
		$fetch_result = json_decode($fetch_string, true);
		if ($fetch_result["result"] != "OK")
		{
			$result["result"] = "NACK";
			$result["nack_details"] = "unable to fetch url; $fetch_url";
			return $result;
		}
		
		$required_states = $then_that_item["required_states"];
		
		// has_to_be_expired check
		if (true)
		{
			if (in_array("has_to_be_expired", $required_states))
			{
				if ($fetch_result["license_data"]["evaluations"]["isexpired"] == true)
				{
					// good
				}
				else
				{
					$result["result"] = "NACK";
					$result["nack_details"]["message"] = "does not meet required state; license is not expired?";
					$result["nack_details"]["fetch_result"] = $fetch_result;
					return $result;
				}
			}
		}
		
		// has_to_be_license_type_hosting check
		if (true)
		{
			if (in_array("has_to_be_license_type_hosting", $required_states))
			{
				if ($fetch_result["license_data"]["license_type"] == "hosting")
				{
					// good
				}
				else
				{
					$result["result"] = "NACK";
					$result["nack_details"]["message"] = "license_type != hosting";
					$result["nack_details"]["fetch_result"] = $fetch_result;
					return $result;
				}
			}
		}
	}
	
	//
	
	$result["result"] = "OK";
	return $result;
}