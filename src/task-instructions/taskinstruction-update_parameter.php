<?php

function nxs_task_instance_do_update_parameter($then_that_item, $taskid, $taskinstanceid)
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
		$parameter = $then_that_item["parameter"];
		$action = $then_that_item["action"];

		if (false)
		{
		}
		else if ($action == "append_wp_post_content")
		{
			// pull current value from the task instance
			$value_json = $inputparameters[$parameter];
			if ($value_json == "")
			{
				$value	= array();
			}
			else
			{
				$value = json_decode($value_json, true);
			}
			
			$process_current = true;
			// in future allow a condition to determine whether or not to process this item
			
			if ($process_current)
			{
				$wp_post_content_type = $then_that_item["wp_post_content_type"];
				$fields_to_ignore = array("type", "parameter", "action", "wp_post_content_type", "marker");
				$fields = array();
				foreach ($then_that_item as $key => $val)
				{
					if (in_array($key, $fields_to_ignore))
					{
						// to be ignored
						continue;
					}
					$fields[$key] = $val;
				}
	
				$value[] = array
				(
					"type" => $wp_post_content_type,
					"fields" => $fields
				);
				
				$value_json = json_encode($value);
				// apply input parameters
				$value_json = nxs_filter_translatesingle($value_json, "{{", "}}", $inputparameters);
				
				
		  	if (nxs_tasks_isheadless())
				{
					$result["console"][] = "storing value $value for $set_inputparameter_field";
					//
					nxs_tasks_appendinputparameter_for_taskinstance($taskid, $taskinstanceid, $parameter, $value_json);
				}
				else
				{
					if (true)
					{
						$result["console"][] = "click on the button to update parameter $parameter to:";
						foreach ($value as $row)
						{
							$type = $row["type"];
							$fields = $row["fields"];
							$result["console"][] = "type: $type fields: " . json_encode($fields);
						}
						
						global $nxs_gl_recipe_instruction_pointer;
						$recipe_hash = $nxs_gl_recipe_instruction_pointer["recipe_hash"];
						$linenr = $nxs_gl_recipe_instruction_pointer["linenr"];
						
						$finished_instruction_pointer = "{$recipe_hash}_{$linenr}";
						nxs_task_setfinishedinstructionpointer($taskid, $taskinstanceid, $finished_instruction_pointer);
						
						//
						$currenturl = nxs_geturlcurrentpage();
						$returnurl = "{$currenturl}";
						$returnurl = "{$returnurl}#{$marker}";
						$button_text = nxs_render_html_escape_singlequote("update parameter $parameter");
						$escaped_value = nxs_render_html_escape_singlequote($value_json);
						
						$html = <<<EOD
											<div style='margin-left: 50px;'>
												<form action='https://global.nexusthemes.com/' method='POST'>
													<input type='hidden' name='nxs' value='task-gui' />
													<input type='hidden' name='action' value='updateparameter' />
													<input type='hidden' name='page' value='taskinstancedetail' />
													<input type='hidden' name='taskid' value='{$taskid}' />
													<input type='hidden' name='taskinstanceid' value='{$taskinstanceid}' />
													<input type='hidden' name='name' value='{$parameter}' />
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
		}
	}
	else
	{
		$result["console"][] = "wont do anything here because of state $state";
	}
	  
  $result["result"] = "OK";
	
  return $result;
}