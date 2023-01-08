<?php

function nxs_task_instance_do_set_parameter($then_that_item, $taskid, $taskinstanceid)
{
	$marker = $then_that_item["marker"];
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = $instancemeta["inputparameters"];
	
	$state = $instancemeta["state"];

  $result = array();
  
	if (!nxs_tasks_isheadless())
	{
		global $nxs_gl_recipe_instruction_pointer;
		
		$linenr = $nxs_gl_recipe_instruction_pointer["linenr"];
	}
	
	if (false)
	{
	}
	else if ($state == "STARTED")
	{
		$multiparameterindex = -1;
		$in_multiparameter_loop = true;
		while ($in_multiparameter_loop)
		{
			$process_current = true;
			
			if ($multiparameterindex == -1)
			{
				$field_attribute = "set_inputparameter_field";
			}
			else
			{
				$field_attribute = "set_inputparameter_field_{$multiparameterindex}";	// for example set_inputparameter_field_0, set_inputparameter_field_1
			}
			
			$set_inputparameter_field = $then_that_item[$field_attribute];
			if (!isset($then_that_item[$field_attribute]))
			{
				if ($multiparameterindex == -1)
				{
					$multiparameterindex++;
					// proceed with the next one
					continue;
				}
				else
				{
					// this means end of the multiparameter loop
					$in_multiparameter_loop = false;
					break;
				}
			}
			else if ($set_inputparameter_field == "")
			{
				$result["nackdetails"] = "nxs_task_instance_do_set_parameter; set_inputparameter_field attribute ({$field_attribute}) is empty";
		  	$result["result"] = "NACK";
		  	return $result;
			}
			
			// pull current value from the task instance
			$existing_value = $inputparameters[$set_inputparameter_field];
			
			// conditionally ignore if already has a value set (so it wont be overriden)
			$skipsettingwhennotempty = $then_that_item["skipsettingwhennotempty"];
			if ($skipsettingwhennotempty || $skipsettingwhennotempty == "true")
			{
				if ($existing_value != "")
				{
					$result["console"][] = "skipsettingwhennotempty is configured";
					$result["console"][] = "skipping set parameter as parameter already has some value ($existing_value)";
					
					$process_current = true;
				}
			}
			
			if ($process_current)
			{
			  $function = $then_that_item["function"];
			  if (false)
			  {
			  	//
			  }
			  else if ($function == "staticvalue")
			  {
			  	
			  	
			  	//error_log("nxs_task_instance_do_set_parameter; staticvalue");
					
					$value = $then_that_item["value"];
					
					// apply input parameters
					$value = nxs_filter_translatesingle($value, "{{", "}}", $inputparameters);
					
					$value_post_processor = $then_that_item["value_post_processor"];
					if (false)
					{
						//
					}
					else if ($value_post_processor == "")
					{
					}
					else if ($value_post_processor == "slugify")
					{
						$value = nxs_url_slugify($value);
					}
					else
					{
						nxs_webmethod_return_nack("value_post_processor not supported? $value_post_processor");
					}			
		
					$existing_value = $inputparameters[$set_inputparameter_field];
					
			  	if (nxs_tasks_isheadless())
					{
						$result["console"][] = "storing value $value for $set_inputparameter_field";
						//
						nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $set_inputparameter_field, $value);
					}
					else
					{
						if ($value == $existing_value && isset($inputparameters[$set_inputparameter_field]))
						{
							$result["console"][] = "<div style='opacity: 0.5; font-size: 70%; font-style: italic;'>nothing to do; field $set_inputparameter_field already has value '<span style='font-family: courier; font-style: italic; background-color: #eee;'>$value</span>'</div>";
						}
						else
						{
							$result["console"][] = "click on the button to store value '<span style='font-family: courier; font-style: italic; background-color: #eee;'>$value</span>' for $set_inputparameter_field";
							
							global $nxs_gl_recipe_instruction_pointer;
							
							$recipe_hash = $nxs_gl_recipe_instruction_pointer["recipe_hash"];
							$linenr = $nxs_gl_recipe_instruction_pointer["linenr"];
							
							$finished_instruction_pointer = "{$recipe_hash}_{$linenr}";
							nxs_task_setfinishedinstructionpointer($taskid, $taskinstanceid, $finished_instruction_pointer);
							
							//
							$currenturl = nxs_geturlcurrentpage();
							$returnurl = "{$currenturl}";
							// $returnurl = nxs_addqueryparametertourl_v2($returnurl, "finished_instruction_pointer", "{$recipe_hash}_{$linenr}", true, true);
							$returnurl = "{$returnurl}#{$marker}";
							$button_text = nxs_render_html_escape_singlequote("store value $value for $set_inputparameter_field");
							if (strlen($value) > 64)
							{
								
								$cutvalue = substr($value, 0, 60);
								$button_text = nxs_render_html_escape_singlequote("store value $cutvalue for $set_inputparameter_field");
							}
							
							
							$escaped_value = nxs_render_html_escape_singlequote($value);
							
							// escape new line chars
							$escaped_value = str_replace("\r", "\\r", $escaped_value);
							$escaped_value = str_replace("\n", "\\n", $escaped_value);
							
							$html = <<<EOD
								<div style='margin-left: 50px;'>
									<form action='https://global.nexusthemes.com/' method='POST'>
										<input type='hidden' name='nxs' value='task-gui' />
										<input type='hidden' name='action' value='updateparameter' />
										<input type='hidden' name='page' value='taskinstancedetail' />
										<input type='hidden' name='taskid' value='{$taskid}' />
										<input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' />
										<input type='hidden' name='name' value='{$set_inputparameter_field}' />
										<!-- function staticvalue -->
										<input type='hidden' name='value' value='{$escaped_value}' />
										<input type='hidden' name='returnurl' value='{$returnurl}' />
										<input type='submit' value='{$button_text}' style='background-color: #CCC;'>
									</form>
								</div>
		EOD;
							$html = str_replace("\n", "", $html);
							$html = str_replace("\r", "", $html);
							$result["console"][]= $html;
						}
					}
			  }
			  else if ($function == "adjusttimestamp")
			  {
			  	$result["console"][] = "then_that_item props: " . json_encode($then_that_item) . "<br />";
			  	
			  	$set_inputparameter_field = $then_that_item["set_inputparameter_field"];
					if ($set_inputparameter_field == "")
					{
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; set_inputparameter_field not set";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
					// value is for example 00:53
					$timestamp = $then_that_item["timestamp"];
					
					$result["console"][] = "timestamp: $timestamp<br />";
					
					// apply input parameters
					$value = nxs_filter_translatesingle($timestamp, "{{", "}}", $inputparameters);
					
					$value_pieces = explode(":", $value);
					
					$minutes = $value_pieces[0];
					$seconds = $value_pieces[1];
					
					$result["console"][] = "minutes: $minutes<br />";
					$result["console"][] = "seconds: $seconds<br />";
					
					$allseconds = ($minutes * 60) + $seconds;
					
					$addseconds = $then_that_item["addseconds"];
					// apply input parameters
					$addseconds = nxs_filter_translatesingle($addseconds, "{{", "}}", $inputparameters);
					
					$allseconds += $addseconds;
					
					$result["console"][] = "allseconds: $allseconds<br />";
					
					$new_minutes = floor($allseconds / 60);
					$new_seconds = $allseconds % 60;
					
					$value = str_pad($new_minutes, 2, "0", STR_PAD_LEFT) . ":" . str_pad($new_seconds, 2, "0", STR_PAD_LEFT);
					
					$existing_value = $inputparameters[$set_inputparameter_field];	// for example 00:53
					
			  	if (nxs_tasks_isheadless())
					{
						$result["console"][] = "storing value $value for $set_inputparameter_field";
						//
						nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $set_inputparameter_field, $value);
					}
					else
					{
						if ($value == $existing_value && isset($inputparameters[$set_inputparameter_field]))
						{
							$result["console"][] = "<div style='opacity: 0.5; font-size: 70%; font-style: italic;'>nothing to do; field $set_inputparameter_field already has value '<span style='font-family: courier; font-style: italic; background-color: #eee;'>$value</span>'</div>";
						}
						else
						{
							$result["console"][] = "click on the button to store value '<span style='font-family: courier; font-style: italic; background-color: #eee;'>$value</span>' for $set_inputparameter_field";
							
							global $nxs_gl_recipe_instruction_pointer;
							$recipe_hash = $nxs_gl_recipe_instruction_pointer["recipe_hash"];
							$linenr = $nxs_gl_recipe_instruction_pointer["linenr"];
							
							$finished_instruction_pointer = "{$recipe_hash}_{$linenr}";
							nxs_task_setfinishedinstructionpointer($taskid, $taskinstanceid, $finished_instruction_pointer);
							
							//
							$currenturl = nxs_geturlcurrentpage();
							$returnurl = "{$currenturl}";
							// $returnurl = nxs_addqueryparametertourl_v2($returnurl, "finished_instruction_pointer", "{$recipe_hash}_{$linenr}", true, true);
							$returnurl = "{$returnurl}#{$marker}";
							$button_text = nxs_render_html_escape_singlequote("store value $value for $set_inputparameter_field");
							$escaped_value = nxs_render_html_escape_singlequote($value);
							
							$html = <<<EOD
								<div style='margin-left: 50px;'>
									<form action='https://global.nexusthemes.com/' method='POST'>
										<input type='hidden' name='nxs' value='task-gui' />
										<input type='hidden' name='action' value='updateparameter' />
										<input type='hidden' name='page' value='taskinstancedetail' />
										<input type='hidden' name='taskid' value='{$taskid}' />
										<input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' />
										<input type='hidden' name='name' value='{$set_inputparameter_field}' />
										<input type='hidden' name='value' value='{$escaped_value}' />
										<input type='hidden' name='returnurl' value='{$returnurl}' />
										<input type='submit' value='{$button_text}' style='background-color: #CCC;'>
									</form>
								</div>
		EOD;
							$html = str_replace("\n", "", $html);
							$html = str_replace("\r", "", $html);
							$result["console"][]= $html;
						}
					}
			  }
			  // oud; moet worden herschreven, maar is nog wel in gebruik!
			  else if ($function == "ixplatform.row.fieldvalue")
			  {
			  	$set_inputparameter_field = $then_that_item["set_inputparameter_field"];
					if ($set_inputparameter_field == "")
					{
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; set_inputparameter_field not set";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
			  	$schema = $then_that_item["schema"];
					if ($schema == "")
					{
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; schema not set";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
					$ixplatform_filter = $then_that_item["ixplatform_filter"];
					
					//error_log("filter is; $ixplatform_filter");
					
					if ($ixplatform_filter != "")
					{
						// apply inputparameters
						$ixplatform_filter = nxs_filter_translatesingle($ixplatform_filter, "{{", "}}", $inputparameters);
						
						if (nxs_stringcontains($ixplatform_filter, "{"))
						{
							if (nxs_tasks_isheadless())
							{
								$result["nackdetails"] = "nxs_task_instance_do_set_parameter; ixplatform_filter still has unreplaced placeholders; $ixplatform_filter";
								$result["result"] = "NACK";
								return $result;
							}
							else
							{
								$result["console"][] = "set_parameter; wont do anything here the filter cannot (yet?) be evaluated because of an unreplaced placeholder ($ixplatform_filter)";
								$result["result"] = "OK";
								return $result;
							}
						}
					}
					else
					{
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; ixplatform_filter not set";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
					//$result["console"][] = "debug; filter is: $ixplatform_filter";
					
					//error_log("ixplatformfilter: $ixplatform_filter");
					
					if (substr_count($ixplatform_filter, "\"") > 2)
					{
						if (nxs_tasks_isheadless())
						{
							$result["result"] = "NACK";
					  	return $result;
						}
						else
						{
							$result["console"][] = "<span style='background-color: red; color: white;'>NACK; ixplatform_filter contains double quote(s)</span>";
							$result["result"] = "OK";
					  	return $result;
						}
					}
					
					$field_to_pick = $then_that_item["field_to_pick"];
					if ($field_to_pick == "")
					{
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; field_to_pick not set";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
					// pull the data
					global $nxs_g_modelmanager;
					$q = "[nxsstring ops='listmodeluris' singularschema='{$schema}' {$ixplatform_filter}]";
					
					//error_log("do_shortcode on $q");
					
					$matchingmodeluris = do_shortcode($q);
					$matchingmodeluri_list = array_filter(explode(";", $matchingmodeluris));
					
					$matchingmodeluri_count = count($matchingmodeluri_list);
					if ($matchingmodeluri_count == 0)
					{
						$result["console"][] = "<span style='background-color: red; color: white;'>please upgrade the shortcode to function 'ixplatform' instead of 'ixplatform.row.fieldvalue'</span>";
						$result["console"][] = "nxs_task_instance_do_set_parameter; 0 rows returned, expected 1; set_inputparameter_field: $set_inputparameter_field";
				  	$result["result"] = "OK";
				  	return $result;
					}
					else if ($matchingmodeluri_count > 1)
					{
						$result["console"][] = "<span style='background-color: red; color: white;'>please upgrade the shortcode to function 'ixplatform' instead of 'ixplatform.row.fieldvalue'</span>";
						$result["console"][] = "nxs_task_instance_do_set_parameter; {$matchingmodeluri_count} rows returned, expected 1";
				  	$result["result"] = "OK";
				  	return $result;
					}
					else 
					{
						$matchingmodeluri = $matchingmodeluri_list[0];
						$a = array
						(
							"modeluri" => $matchingmodeluri,
						);
						$entry = $nxs_g_modelmanager->getmodeltaxonomyproperties($a);
						
						//error_log("set_parameter; found; " . json_encode($entry));
						
						if (!isset($entry[$field_to_pick]))
						{
							$result["nackdetails"] = "nxs_task_instance_do_set_parameter; field_to_pick no property of matching entry of modeluri {$matchingmodeluri}? (did you misspell the column/property?) " . json_encode($entry);
					  	$result["result"] = "NACK";
					  	return $result;
						}
						
						// delegate rendering to set_parameter using static value for static key
						$value = $entry[$field_to_pick];
					}
					
					//
					//
					//
					
					
					
					//$result["console"][] = do_shortcode("[nxs_p001_task_instruction indent=0 type='set_parameter' set_inputparameter_field='{$set_inputparameter_field}' function='staticvalue' value='{$value}']");
					$delegated_then_that_item = array
					(
						"type" => "set_parameter",
						"marker" => $marker,
						"set_inputparameter_field" => "{$set_inputparameter_field}",
						"function" => "staticvalue",
						"value" => $value
					);
					$delegated_output = nxs_task_instance_do_set_parameter($delegated_then_that_item, $taskid, $taskinstanceid);
					if ($delegated_output["result"] != "OK")
					{
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; error delegating";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
					// replicate output from delegated part to "this" part
					foreach ($delegated_output["console"] as $line)
					{
						$result["console"][] = $line;
					}
			  }
			  else if ($function == "ixplatform")
			  {
			  	$set_inputparameter_field = $then_that_item["set_inputparameter_field"];
					if ($set_inputparameter_field == "")
					{
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; set_inputparameter_field not set";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
			  	$schema = $then_that_item["schema"];
					if ($schema == "")
					{
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; schema not set";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
					$ixplatform_filter = $then_that_item["ixplatform_filter"];
					if ($ixplatform_filter != "")
					{
						// apply inputparameters
						$ixplatform_filter = nxs_filter_translatesingle($ixplatform_filter, "{{", "}}", $inputparameters);
						
						if (nxs_stringcontains($ixplatform_filter, "{"))
						{
							if (nxs_tasks_isheadless())
							{
								$result["nackdetails"] = "nxs_task_instance_do_set_parameter; ixplatform_filter still has unreplaced placeholders; $ixplatform_filter";
								$result["result"] = "NACK";
								return $result;
							}
							else
							{
								$result["console"][] = "set_parameter; wont do anything here the filter cannot (yet?) be evaluated because of an unreplaced placeholder ($ixplatform_filter)";
								$result["result"] = "OK";
								return $result;
							}
						}
					}
					else
					{
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; ixplatform_filter not set";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
					//$result["console"][] = "debug; filter is: $ixplatform_filter";
					
					//error_log("ixplatformfilter: $ixplatform_filter");
					if (substr_count($ixplatform_filter, "\"") > 2)
					{
						if (nxs_tasks_isheadless())
						{
							$result["result"] = "NACK";
					  	return $result;
						}
						else
						{
							$result["console"][] = "<span style='background-color: red; color: white;'>NACK; ixplatform_filter contains double quote(s)</span>";
							$result["result"] = "OK";
					  	return $result;
						}
					}
					
					$result["result"] = "OK";
					
					// pull the data
					global $nxs_g_modelmanager;
					$q = "[nxs_string ops='listmodeluris' singularschema='{$schema}' {$ixplatform_filter}]";
					
					$matchingmodeluris = do_shortcode($q);
					$matchingmodeluri_list = array_filter(explode(";", $matchingmodeluris));	// removes empty items
		
					if ($then_that_item["debug"] == "true")
					{
						$result["console"][] = "applied query; $q";
					}
					
					$matchingmodeluri_count = count($matchingmodeluri_list);
					if ($matchingmodeluri_count == 0)
					{
						$not_found_behaviour = $then_that_item["not_found_behaviour"];
						if ($not_found_behaviour == "")
						{
							$result["nackdetails"] = "nxs_task_instance_do_set_parameter; 0 rows returned, expected 1";
					  	$result["result"] = "NACK";
					  	return $result;
					  }
					  else if (nxs_stringstartswith($not_found_behaviour, "returnstatic:"))
					  {
					  	$value = str_replace("returnstatic:", "", $not_found_behaviour);
					  	//
					  }
					  $escaped_q = $q;
					  $escaped_q = str_replace("'", "&apos;", $escaped_q);
					  $result["console"][] = "<span title='no rows returned for $escaped_q'>hover to see no rows returned hint</span>";
					}
					else if ($matchingmodeluri_count > 1)
					{
						if ($then_that_item["debug"] == "true")
						{
							$result["console"][] = "debug; multi found; $matchingmodeluris";
						}
						
						$multi_found_behaviour = $then_that_item["multi_found_behaviour"];
						if ($multi_found_behaviour == "")
						{
							$result["nackdetails"] = "nxs_task_instance_do_set_parameter; {$matchingmodeluri_count} rows returned, expected 1";
					  	$result["result"] = "NACK";
					  	return $result;
					  }
					  else if (nxs_stringstartswith($multi_found_behaviour, "returnstatic:"))
					  {
					  	$value = str_replace("returnstatic:", "", $multi_found_behaviour);
					  	//
					  }
					  else
					  {
					  	$result["nackdetails"] = "nxs_task_instance_do_set_parameter; unsupported multi_found_behaviour; $multi_found_behaviour";
					  	$result["result"] = "NACK";
					  	return $result;
					  }
					}
					else if ($matchingmodeluri_count == 1)
					{
						//error_log("matchingmodeluri_count=1");
						//error_log($q);
						//error_log(":) value:'" . $matchingmodeluris . "'");
						
						$one_found_behaviour = $then_that_item["one_found_behaviour"];
						if ($one_found_behaviour == "returnfield")
						{
							$field_to_return = $then_that_item["field_to_return"];
							if ($field_to_return == "")
							{
								if (nxs_tasks_isheadless())
								{
									$result["nackdetails"] = "nxs_task_instance_do_set_parameter; field_to_return not set";
							  	$result["result"] = "NACK";
							  	return $result;
							  }
							  else
							  {
							  	$result["console"][] = "<span style='background-color: red; color: white;'>NACK; nxs_task_instance_do_set_parameter; field_to_return not set</span>";
									$result["result"] = "OK";
							  	return $result;
							  }
							}
							
							$matchingmodeluri = $matchingmodeluri_list[0];
							$a = array
							(
								"modeluri" => $matchingmodeluri,
							);
							$entry = $nxs_g_modelmanager->getmodeltaxonomyproperties($a);
							
							//error_log("set_parameter; found; " . json_encode($entry));
						
							if (!isset($entry[$field_to_return]))
							{
								if (nxs_tasks_isheadless())
								{
									$result["nackdetails"] = "nxs_task_instance_do_set_parameter; return field ('{$field_to_return}') no property of matching entry? (did you misspell the column/property?)";
							  	$result["result"] = "NACK";
							  	return $result;
							  }
							  else
							  {
							  	$result["console"][] = "<span style='background-color: red; color: white;'>NACK; nxs_task_instance_do_set_parameter; return field ('{$field_to_return}') no property of matching entry? (did you misspell the column/property?)<br />Shortcode $q<br />Model uri: '$matchingmodeluri' returned: " . json_encode($entry) . "</span>";
									$result["result"] = "OK";
							  	return $result;					  	
							  }
							}
						
							// delegate rendering to set_parameter using static value for static key
							$value = $entry[$field_to_return];
						}
						else if (nxs_stringstartswith($one_found_behaviour, "returnstatic:"))
					  {
					  	$value = str_replace("returnstatic:", "", $one_found_behaviour);
					  	//
					  }
					  else
					  {
					  	$result["nackdetails"] = "nxs_task_instance_do_set_parameter; unsupported one_found_behaviour; $one_found_behaviour";
					  	$result["result"] = "NACK";
					  	return $result;
					  }
					}
					else
					{
						// impossible to end up here
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; should not be possible to end up here";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
					/*
					if (nxs_stringcontains($value, "\r\n"))
					{
						echo "TESTING123:";
						var_dump($value);
						die();
					}
					*/
					
					// 2022 01 25; added support for empty_behaviour
					if ($value == "")
					{
						$empty_behaviour = $then_that_item["empty_behaviour"];
						if ($empty_behaviour != "")
						{
							if (nxs_stringstartswith($empty_behaviour, "returnstatic:"))
						  {
						  	$value = str_replace("returnstatic:", "", $empty_behaviour);
						  	//
						  }
						  else
						  {
						  	$result["nackdetails"] = "nxs_task_instance_do_set_parameter; unsupported empty_behaviour; $empty_behaviour (possible values; returnstatic:XYZ)";
						  	$result["result"] = "NACK";
						  	return $result;
						  }			  
					  }
					  else
					  {
					  	// leave as-is
					  }
					}
					
					
					//
					//
					//
					
					$delegated_then_that_item = array
					(
						"type" => "set_parameter",
						"marker" => $marker,
						"set_inputparameter_field" => "{$set_inputparameter_field}",
						"function" => "staticvalue",
						"value" => $value
					);
					$delegated_output = nxs_task_instance_do_set_parameter($delegated_then_that_item, $taskid, $taskinstanceid);
					if ($delegated_output["result"] != "OK")
					{
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; error delegating";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
					// replicate output from delegated part to "this" part
					foreach ($delegated_output["console"] as $line)
					{
						$result["console"][] = $line;
					}
					
					$result["console"][] = "* <div style='display: inline-block; font-size: 70%; font-style: italic; opacity: 0.5;'>Value is derived from schema $schema</div>";
					$result["console"][] = do_shortcode("** <div style='display: inline-block; font-size: 70%; font-style: italic; opacity: 0.5;'>[nxs_p001_task_instruction type='open-ixplatform-table' schema='{$schema}']</div>");			
			  }
			  else if ($function == "taskinstance")
			  {
			  	$set_inputparameter_field = $then_that_item["set_inputparameter_field"];
					if ($set_inputparameter_field == "")
					{
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; set_inputparameter_field not set";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
					// f_taskid
					// f1_key
					// f1_op
					// f1_equals
					// f_states
					
					// ------
					// construct the search arguments
				  if (true)
				  {
					  $subconditions = array();
					  
					  $f_states = $then_that_item["f_states"];
					  if ($f_states != "")
					  {
					  	$subconditions[] = array
							(
								"type" => "true_if_in_any_of_the_required_states",
								"any_of_the_required_states" => explode("|", $f_states),
							);
					  }
					  
						$f_taskid = $then_that_item["f_taskid"];
					  if ($f_taskid != "")
					  {
					  	$subconditions[] = array
							(
								"type" => "true_if_task_has_required_taskid",
								"required_taskid" => $f_taskid
							);
					  }
					  
					  $f1_key = $then_that_item["f1_key"];
					  $f1_op = $then_that_item["f1_op"];
					  if ($f1_key != "")
					  {
					  	if ($f1_op == "equals")
					  	{
							  $f1_equals = $then_that_item["f1_equals"];
							  $f1_equals = nxs_filter_translatesingle($f1_equals, "{{", "}}", $inputparameters);
		
					  		$subconditions[] = array
								(
									"type" => "true_if_inputparameter_has_required_value_for_key",
									"key" => $f1_key,
									"required_value" => $f1_equals
								);
					  	}
					  	else
					  	{
					  		// not supported
					  	}
					  	
					  }
					  
					  $search_args = array
						(
							"if_this" => array
							(
								"type" => "true_if_each_subcondition_is_true",
								"subconditions" => $subconditions,
							),
						);
					}
					$taskinstances_wrap = nxs_tasks_searchtaskinstances($search_args);
					$taskinstances = $taskinstances_wrap["taskinstances"];
					
					
					$matchcount = count($taskinstances);
					if ($matchcount == 0)
					{
						$not_found_behaviour = $then_that_item["not_found_behaviour"];
						if (false)
						{
						}
						else if ($not_found_behaviour == "")
						{
							$result["nackdetails"] = "nxs_task_instance_do_set_parameter; 0 rows returned, expected 1";
					  	$result["result"] = "NACK";
					  	return $result;
					  }
					  else if (nxs_stringstartswith($not_found_behaviour, "returnstatic:"))
					  {
					  	$value = str_replace("returnstatic:", "", $not_found_behaviour);
					  	//
					  }
					  else
					  {
					  	$result["nackdetails"] = "nxs_task_instance_do_set_parameter; 0 rows returned, unsupported not_found_behaviour; $not_found_behaviour";
					  	$result["result"] = "NACK";
					  	return $result;
					  }
					}
					else if ($matchcount > 1)
					{
						$multi_found_behaviour = $then_that_item["multi_found_behaviour"];
						if (false)
						{
							//
						}
						else if ($multi_found_behaviour == "")
						{
							$result["nackdetails"] = "nxs_task_instance_do_set_parameter; multiple rows returned, expected 1";
					  	$result["result"] = "NACK";
					  	return $result;
					  }
					  else if (nxs_stringstartswith($multi_found_behaviour, "returnstatic:"))
					  {
					  	$value = str_replace("returnstatic:", "", $multi_found_behaviour);
					  	//
					  }
					  else
					  {
					  	$result["nackdetails"] = "nxs_task_instance_do_set_parameter; unsupported multi_found_behaviour; $multi_found_behaviour";
					  	$result["result"] = "NACK";
					  	return $result;
					  }
					}
					else if ($matchcount == 1)
					{
						$one_found_behaviour = $then_that_item["one_found_behaviour"];
						if (false)
						{
							//
						}
						else if ($one_found_behaviour == "returnfield")
						{
							$field_to_return = $then_that_item["field_to_return"];
							if ($field_to_return == "")
							{
								if (nxs_tasks_isheadless())
								{
									$result["nackdetails"] = "nxs_task_instance_do_set_parameter; field_to_return not set";
							  	$result["result"] = "NACK";
							  	return $result;
							  }
							  else
							  {
							  	$result["console"][] = "<span style='background-color: red; color: white;'>NACK; nxs_task_instance_do_set_parameter; field_to_return not set</span>";
									$result["result"] = "OK";
							  	return $result;
							  }
							}
							
							$matchtaskid = $taskinstances[0]["taskid"];
							$matchtaskinstanceid = $taskinstances[0]["taskinstanceid"];
							$entry = nxs_task_getinstanceinputparameters($matchtaskid, $matchtaskinstanceid);
							
							//error_log("set_parameter; found; " . json_encode($entry));
						
							if (!isset($entry[$field_to_return]))
							{
								if (nxs_tasks_isheadless())
								{
									$result["nackdetails"] = "nxs_task_instance_do_set_parameter; field_to_return no property of matching entry? (did you misspell the column/property?)";
							  	$result["result"] = "NACK";
							  	return $result;
							  }
							  else
							  {
							  	$result["console"][] = "<span style='background-color: red; color: white;'>NACK; nxs_task_instance_do_set_parameter; return field ('{$field_to_return}') no property of matching entry? (did you misspell the column/property?)<br />Shortcode $q<br />Model uri: '$matchingmodeluri' returned: " . json_encode($entry) . "</span>";
									$result["result"] = "OK";
							  	return $result;					  	
							  }
							}
						
							// delegate rendering to set_parameter using static value for static key
							$value = $entry[$field_to_return];
						}
						else if (nxs_stringstartswith($one_found_behaviour, "returnstatic:"))
					  {
					  	$value = str_replace("returnstatic:", "", $one_found_behaviour);
					  	//
					  }
					  else
					  {
					  	$result["nackdetails"] = "nxs_task_instance_do_set_parameter; unsupported one_found_behaviour; $one_found_behaviour";
					  	$result["result"] = "NACK";
					  	return $result;
					  }
					}
					else
					{
						// impossible to end up here
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; should not be possible to end up here";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
					//
					//
					//
					
					$delegated_then_that_item = array
					(
						"type" => "set_parameter",
						"marker" => $marker,
						"set_inputparameter_field" => "{$set_inputparameter_field}",
						"function" => "staticvalue",
						"value" => $value
					);
					$delegated_output = nxs_task_instance_do_set_parameter($delegated_then_that_item, $taskid, $taskinstanceid);
					if ($delegated_output["result"] != "OK")
					{
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; error delegating";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					
					// replicate output from delegated part to "this" part
					foreach ($delegated_output["console"] as $line)
					{
						$result["console"][] = $line;
					}
			  }
			  else if ($function == "shortcode")
			  {
			  	//error_log("set_parameter;shortcode; " . json_encode($then_that_item));
			  	
			  	$shortcode_type = $then_that_item["shortcode_type"];
			  	$shortcode_input = $then_that_item["shortcode_input"];
			  	$shortcode_input = nxs_filter_translatesingle($shortcode_input, "{{", "}}", $inputparameters);
			  	
			  	$set_inputparameter_field = $then_that_item["set_inputparameter_field"];
					if ($set_inputparameter_field == "")
					{
						$result["nackdetails"] = "nxs_task_instance_do_set_parameter; set_inputparameter_field not set";
				  	$result["result"] = "NACK";
				  	return $result;
					}
					$moreshortcodeatts = "";
					$exclude_keys = array("shortcode_input", "shortcode_type", "type", "set_inputparameter_field", "type");
					foreach ($then_that_item as $k => $v)
					{
						if (!in_array($k, $exclude_keys))
						{
							// error_log("set_parameter $k $v");
							
							$pimped_v = $v;
							$pimped_v = nxs_filter_translatesingle($pimped_v, "{{", "}}", $inputparameters);
							
							$moreshortcodeatts .= "{$k}='{$pimped_v}' ";
						}
					}
		
					$shortcode = "[{$shortcode_type} value='{$shortcode_input}' $moreshortcodeatts]";
					if ($then_that_item["debug"] == "true")
					{
						$result["console"][] = "applying shortcode; $shortcode";
					}
					
					$value = do_shortcode($shortcode);
					
					$value_post_processor = $then_that_item["value_post_processor"];
					if (false)
					{
						//
					}
					else if ($value_post_processor == "")
					{
					}
					else if ($value_post_processor == "slugify")
					{
						$value = nxs_url_slugify($input);
					}
					else if ($value_post_processor == "nxs_replace_special_chars")
					{
						$value = str_replace("&quot;", "__nxs:singlequote__", $value);
						$value = str_replace("'", "__nxs:singlequote__", $value);
						// convert double quote to single quote
						$value = str_replace("\"", "__nxs:singlequote__", $value);
					}
					else
					{
						nxs_webmethod_return_nack("value_post_processor not supported? $value_post_processor");
					}
					
					$existing_value = $inputparameters[$set_inputparameter_field];
					
			  	if (nxs_tasks_isheadless())
					{
						$result["console"][] = "storing value $value for $set_inputparameter_field";
						//
						nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $set_inputparameter_field, $value);
					}
					else
					{
						if ($value == $existing_value)
						{
							$result["console"][] = "<div style='opacity: 0.5; font-size: 70%; font-style: italic;'>nothing to do; field $set_inputparameter_field already has value <span style='font-family: courier; font-style: italic; background-color: #eee;'>$value</span></div>";
						}
						else
						{
							$ismultiline = false;
							if (false)
							{
							}
							else if (nxs_stringcontains($value, "\r\n"))
							{
								$ismultiline = true;
							}
							else if (nxs_stringcontains($value, "<br"))
							{
								$ismultiline = true;
							}
							else
							{
								//error_log("debugging 123; $value");
							}
		
							if (!$ismultiline)
							{	
								$result["console"][] = "click on the button to store value <span style='font-family: courier; font-style: italic; background-color: #eee;'>$value</span> for $set_inputparameter_field";
							}
							else
							{
								$result["console"][] = "click on the button to store value <div style='font-family: courier; font-style: italic; background-color: #eee;'>$value</div> for $set_inputparameter_field";
							}
							
							$currenturl = nxs_geturlcurrentpage();
							$returnurl = "{$currenturl}#{$marker}";
							if (!$ismultiline)
							{
								$button_text = nxs_render_html_escape_singlequote("store value $value for $set_inputparameter_field");
							}
							else
							{
								$button_text = nxs_render_html_escape_singlequote("store lines as shown above for $set_inputparameter_field");
							}
							$escaped_value = nxs_render_html_escape_singlequote($value);
							//$escaped_value = str_replace("\r\n", "X", $escaped_value);
							//$escaped_value = str_replace("\r\n", "&#10;&#13;", $escaped_value);
							$escaped_value = str_replace("\r\n", "&#10;", $escaped_value);
							//$escaped_value = str_replace("\r", "", $escaped_value);
							
							$html = <<<EOD
								<div style='margin-left: 50px;'>
									<form action='https://global.nexusthemes.com/' method='POST'>
										<input type='hidden' name='nxs' value='task-gui' />
										<input type='hidden' name='action' value='updateparameter' />
										<input type='hidden' name='page' value='taskinstancedetail' />
										<input type='hidden' name='taskid' value='{$taskid}' />
										<input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' />
										<input type='hidden' name='name' value='{$set_inputparameter_field}' />
										<input type='hidden' name='value' value='{$escaped_value}' />
										<input type='hidden' name='returnurl' value='{$returnurl}' />
										<input type='submit' value='{$button_text}' style='background-color: #CCC;'>
									</form>
								</div>
		EOD;
							$html = str_replace("\n", "", $html);
							$html = str_replace("\r", "", $html);
							$result["console"][]= $html;
						}
					}
			  }
			  else if ($function == "aws_s3")
			  {
			  	
			  	
			  	$set_inputparameter_field = $then_that_item["set_inputparameter_field"];
			  	$s3bucket = $then_that_item["s3bucket"];
			  	$s3key = $then_that_item["s3key"];
			  	$s3key = nxs_filter_translatesingle($s3key, "{{", "}}", $inputparameters);
			  	
			  	//
			  	if (nxs_stringcontains($s3key, "{"))
			  	{
			  		$result["console"][] = "set_parameter;aws_s3;ERR; s3key has placeholder? ($s3bucket, $s3key)";
			  		$result["result"] = "NACK";
			  		return $result;
			  	}
			  	
			  	require_once("/srv/generic/libraries-available/nxs-aws/nxs_aws_logic.php");
			  	
			  	if (!nxs_s3_fileexists($s3bucket, $s3key))
			  	{
			  		$result["console"][] = "set_parameter;aws_s3;ERR; bucket does not have key? ($s3bucket, $s3key)";
			  		$result["result"] = "OK";
			  		return $result;
			  	}
			  	
			  	$s3args = array
			  	(
			  		"bucket" => $s3bucket,
			  		"keyinbucket" => $s3key
			  	);
			  	
			  	
			  	
			  	$data = nxs_s3_getfiledata($s3args);
			  	$body = $data["Body"];
			  	
			  	$interpret = $then_that_item["interpret"];
			  	if ($interpret == "mime.message.body.text")
			  	{
			  		$plaintext = false;
			  		
			  		$lines = explode("\r\n", $body);
			  		
			  		if ($plaintext == false)
			  		{
				  		for ($i = 0; $i < count($lines); $i++)
				  		{
				  			$line = $lines[$i];
				  			if (nxs_stringstartswith($line, "Content-Type: text/plain"))
				  			{
				  				// starting marker
				  				for ($j = $i + 1; $j < count($lines); $j++)
				  				{
				  					$plaintext .= $lines[$j] . "<br />";
				  					if (nxs_stringstartswith($lines[$j+1], "--00000000"))
				  					{
				  						// end marker
				  						break;
				  					}
				  				}
				  				
				  				break;
				  			}
				  		}
				  	}
			  		
			  		// if its not set, then its possible the mime does not contain a content-type text/plain
			  		// interpret it in another way...
			  		if ($plaintext == false)
			  		{
			  			// find the first empty line, and return everything after this empty line?
			  			for ($i = 0; $i < count($lines); $i++)
				  		{
				  			$line = $lines[$i];
				  			if ($line == "")
				  			{
				  				// starting marker
				  				for ($j = $i + 1; $j < count($lines); $j++)
				  				{
				  					$plaintext .= $lines[$j] . "<br />";
				  				}
				  				
				  				break;
				  			}
				  		}
			  		}
			  		
			  		if ($plaintext == false)
			  		{
			  			$plaintext = "ERR; unable to parse mime (download msg through cyberduck)";
			  		}
			  		
			  		$value = $plaintext;
			  		
			  		$result["console"][] = "storing value $value for $set_inputparameter_field";
						//
						nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $set_inputparameter_field, $value);
			  	}
			  	else
			  	{
			  		//
			  		error_log("taskinstruction;set_parameter;interpret not supported");
			  	}
			  }
			  else
			  {
			  	$result["nackdetails"] = "nxs_task_instance_do_set_parameter; unsupported function; $function";
			  	$result["result"] = "NACK";
			  	return $result;
			  }
			}
		  
		  // loop, unless
		  $multiparameterindex++;
		  if ($multiparameterindex > 1024)
		  {
		  	$result["nackdetails"] = "nxs_task_instance_do_set_parameter; unexpected $multiparameterindex (endless loop?)";
		  	$result["result"] = "NACK";
		  	return $result;
		  }
		}
	}
	else
	{
		$result["console"][] = "wont do anything here because of state $state";
	}
	  
  $result["result"] = "OK";
	
  return $result;
}