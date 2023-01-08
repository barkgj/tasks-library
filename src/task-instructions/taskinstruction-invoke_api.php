<?php

function nxs_task_instance_do_invoke_api($then_that_item, $taskid, $taskinstanceid)
{
	$marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	$runtimeinputparameters = $inputparameters;
	$runtimeinputparameters["taskid"] = $taskid;
	$runtimeinputparameters["taskinstanceid"] = $taskinstanceid;
	
	$state = $instancemeta["state"];
	
  $result = array();
  
  if ($state == "STARTED")
	{
		$result["console"][] = "invoking api";
	  
	  $atts = $then_that_item;
	  
	  $invoke_api_timeoutsecs = $atts["invoke_api_timeoutsecs"];
	  
	  $service = $atts["service"];
	  $service_id = $atts["service_id"];
	  if ($service == "" && $service_id == "") 
	  {
	    $result["result"] = "NACK";
	    $result["nack_details"] = array
	    (
	      "message" => "specify service or service_id"
	    );
	    return $result;
	  }
	  if ($service != "" && $service_id != "") 
	  {
	    $result["result"]       = "NACK";
	    $result["nack_details"] = array
	    (
	      "message" => "ambiguous; service and service_id both specified"
	    );
	    return $result;
	  }
	  
	  if ($service != "") 
	  {
	    // convenience way; input is the service instead of the id
	    $service_schema = "nxs.itil.configurationitems.api.service";
	    global $nxs_g_modelmanager;
	    $a = array
	    (
	      "singularschema" => $service_schema
	    );
	    $allservices = $nxs_g_modelmanager->gettaxonomypropertiesofallmodels($a);
	    foreach ($allservices as $entry) 
	    {
	      $entry_id       = $entry["nxs.itil.configurationitems.api.service_id"];
	      $currentservice = $entry["service"];
	      if ($service == $currentservice) {
	        // found
	        $service_id = $entry_id;
	      }
	    }
	    
	    if ($service_id == "") 
	    {
	      $result["result"] = "NACK";
	      $result["nack_details"] = array(
	        "message" => "shortcode error; {$type} service not found ({$service})"
	      );
	      return $result;
	    }
	  }
	  
	  if ($service_id == "") 
	  {
	    $result["result"]       = "NACK";
	    $result["nack_details"] = array
	    (
	      "message" => "shortcode error; {$type} missing attribute; service or service_id"
	    );
	    return $result;
	  }
	  
	  global $nxs_g_modelmanager;
	  $a = array
	  (
	    "modeluri" => "{$service_id}@nxs.itil.configurationitems.api.service"
	  );
	  $service_properties = $nxs_g_modelmanager->getmodeltaxonomyproperties($a);
	  $api_id = $service_properties["nxs.itil.configurationitems.api_id"];
	  $a = array
	  (
	    "modeluri" => "{$api_id}@nxs.itil.configurationitems.api"
	  );
	  
	  $api_properties = $nxs_g_modelmanager->getmodeltaxonomyproperties($a);
	  
	  $api = $api_properties["api"];
	  $scheme = $api_properties["scheme"];
	  $hostname = $api_properties["hostname"];
	  
	  $service = $service_properties["service"];
	  
	  $stillfoundanyunreplacedplaceholders = false;
	  
	  $invoke_api_url = "{$scheme}://{$hostname}/api/1/prod/{$service}/?nxs={$api}-api&nxs_json_output_format=prettyprint";
	  
	  $parametersforapi = array();
	  $exclude_keys     = array(
	    "service",
	    "service_id",
	    "type",
	    "nxs",
	    "nxs_json_output_format",
	    "indent",
	    "marker",
	    "store_output",
	    "store_output_prefix",
	    "store_output_fields_containing",
	    "invoke_api_accoladebehaviour"
	  );
	  foreach ($atts as $key => $val) 
	  {
	    if (in_array($key, $exclude_keys)) 
	    {
	      // skip
	      continue;
	    }
	    
	    // replace placeholders if any are remaining
	    $val = nxs_filter_translatesingle($val, "{{", "}}", $runtimeinputparameters);
	    
	    if (nxs_stringcontains($val, "{") || nxs_stringcontains($val, "}"))
	    {
	    	$stillfoundanyunreplacedplaceholders = true;
	    	$unreplacedplaceholders[] = $key . " ($val)";
	    }
	    
	    // domain={{domain}}
	    
	    $invoke_api_url = nxs_addqueryparametertourl_v2($invoke_api_url, $key, $val, true, true);
	    
	    $parametersforapi[$key] = $val;
	  }
	  
	  $enabler = md5($invoke_api_url);
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
	  
	  if ($stillfoundanyunreplacedplaceholders == false)
	  {
		  if ($doit)
		  {
		  	$result["console"][] = "invoke_api_url: $invoke_api_url";
	
				// following line is old code; this one timed out		  
			  // $invoke_api_string = file_get_contents($invoke_api_url);
				$url_args["url"] = $invoke_api_url;
				
				if ($invoke_api_timeoutsecs == "")
				{
					$invoke_api_timeoutsecs = 900;
				}
				
				$url_args["timeoutsecs"] = $invoke_api_timeoutsecs;
				$invoke_api_string = nxs_geturlcontents($url_args);
			  $invoke_api_result = json_decode($invoke_api_string, true);
			  
			  $returnsasexpected = false;
			  
			  if ($invoke_api_result["result"] == "OK") 
			  {
			  	$returnsasexpected = true;
			  }
			  else if ($invoke_api_result["result"] == "ALTFLOW") 
			  {
			  	$expected_altflow_ids_string = $then_that_item["expected_altflow_ids"];
			  	$expected_altflow_ids_string = str_replace("|", ";", $expected_altflow_ids_string);
			  	$expected_altflow_ids = explode(";", $expected_altflow_ids_string);
			  	$actual_altflow_id = $invoke_api_result["altflowid"];
			  	if (in_array($actual_altflow_id, $expected_altflow_ids))
			  	{
			  		$returnsasexpected = true;
			  	}
			  	//error_log("got altflow; expected: {$expected_altflow_ids_string}; actual_altflow_id; $actual_altflow_id; conclusion;" . json_encode($returnsasexpected));
			  }
			  
			  if (!$returnsasexpected) 
			  {
			  	// TODO: write property to inputparameters of this instance?
			  	
			  	$message = $invoke_api_result["message"];
			    $result["result"] = "NACK";
			    $result["nack_details_v2"] = array();
			    $result["nack_details"] = array
			    (
			      "invoke_api_result" => $invoke_api_result
			    );
			    return $result;
			  }
			  
			  // optionally store the results of the invocation 
			  // to the inputparameters of the current task instance
				if ($then_that_item["store_output"] == "true")
				{
					$result["console"][] = "planning to store output";
					
					$store_output_prefix = $then_that_item["store_output_prefix"];
					$store_output_fields_containing = $then_that_item["store_output_fields_containing"];
					$store_output_fields_containing_pieces = explode(";", $store_output_fields_containing);
					
					$flattened = nxs_array_flattenarray($invoke_api_result, "{$store_output_prefix}", "_");
					foreach ($flattened as $key => $val)
					{
						$should_include = false;
						if ($store_output_fields_containing == "*")
						{
							$should_include = true;
						}
						else
						{
							$key_without_prefix = substr($key, strlen($store_output_prefix));
							
							foreach ($store_output_fields_containing_pieces as $store_output_fields_containing_piece)
							{
								if (nxs_stringcontains($key_without_prefix, $store_output_fields_containing_piece))
								{
									$should_include = true;
									break;
								}
							}
						}
						
						if ($should_include)
						{
							$result["console"][] = "storing output $key (value: $val)";
							
							// sanitize keys
							$key = str_replace("/", "_FSLASH_", $key);
							$key = str_replace("-", "_", $key);
							
							$instancemeta["inputparameters"][$key] = $val;
						}
						else
						{
							// $result["console"][] = "<span style='color: #ddd;'>output $key will not be stored as it doesnt meet the criteria</span>";
						}
					}
					
					nxs_tasks_updateinstance($taskid, $taskinstanceid, $instancemeta);
				}
				else
				{
					$result["console"][] = "no output configured to be stored";
				}
		  
		  	$result["invoke_api_result"] = $invoke_api_result;
		  	
		  	//
				if (!nxs_tasks_isheadless())
				{
					if ($invoke_api_result["result"] == "OK")
					{
				  	$marker = $then_that_item["marker"];
				  	
				  	global $nxs_gl_recipe_instruction_pointer;
						$recipe_hash = $nxs_gl_recipe_instruction_pointer["recipe_hash"];
						$linenr = $nxs_gl_recipe_instruction_pointer["linenr"];
				  	
				  	$finished_instruction_pointer = "{$recipe_hash}_{$linenr}";
				  	
						$random_reload_gui_token = time();
						
					  $currenturl = nxs_geturlcurrentpage();
						$returnurl = "{$currenturl}";
						$returnurl = nxs_removequeryparameterfromurl($returnurl, "doit");
						// $returnurl = nxs_addqueryparametertourl_v2($returnurl, "finished_instruction_pointer", "{$recipe_hash}_{$linenr}", true, true);
						$returnurl = nxs_addqueryparametertourl_v2($returnurl, "random_reload_gui_token", "{$random_reload_gui_token}", true, true);
						$returnurl = "{$returnurl}#{$marker}";
					  $result["console"][] = "<script>window.location.href = '{$returnurl}';</script>";
					  
					  nxs_task_setfinishedinstructionpointer($taskid, $taskinstanceid, $finished_instruction_pointer);
					}
				}
		  }
		  else
		  {
		  	$currenturl = nxs_geturlcurrentpage();
		  	$action_url = $currenturl;
				$action_url = nxs_addqueryparametertourl_v2($action_url, "doit", $enabler, true, true);
				$result["console"][] = "<span style='background-color: orange; color: white;'>NOTE: procrastinating</span> the invocation of the API because the shortcode is invoked by the GUI (not headless through a workflow/batch)";
				$result["console"][] = "<a href='{$action_url}#$marker'>Click here to invoke API</a>";
				$result["console"][] = "<div style='margin-left: 50px; width: 50vw; color: #777; overflow-wrap: break-word;font-family: courier; font-size: 10px;'>this will invoke url:<br />{$invoke_api_url}</div>";
		  }
		}
		else
		{
			if ($doit)
		  {
		  	$invoke_api_accoladebehaviour = $atts["invoke_api_accoladebehaviour"];
		  	
		  	if (false)
		  	{
		  		//
		  	}
		  	else if ($invoke_api_accoladebehaviour == "")
		  	{
			  	$result["result"] = "NACK";
			  	$details = "";
			  	foreach ($unreplacedplaceholders as $unreplacedplaceholder)
					{
						$details .= "found unreplaced placeholders; {$unreplacedplaceholder} ";
					}
					
			    $result["nack_details"] = array
			    (
			      "message" => "unable to invoke api; {$details}"
			    );
			    
			    return $result;
			  }
			  else if ($invoke_api_accoladebehaviour == "SKIP")
			  {
			  	$result["console"][] = "invoke api skipped; placeholders found (invoke_api_accoladebehaviour=SKIP)";
			  	$result["result"] = "OK";
			  	return $result;
			  }
		  }
		  else
		  {
				$result["console"][] = "unable to invoke api;";
				foreach ($unreplacedplaceholders as $unreplacedplaceholder)
				{
					$result["console"][] = "found unreplaced placeholders; {$unreplacedplaceholder}";
				}
			}
		}
	}
	else
	{
		$result["console"][] = "api_invoke; wont do anything here because of state $state";
	}
	
  // 
  
  $result["result"] = "OK";
  
  return $result;
}