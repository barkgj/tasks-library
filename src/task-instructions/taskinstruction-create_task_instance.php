<?php

use barkgj\functions;
use barkgj\tasks;
use barkgj\tasks\itaskinstruction;

class create_task_instance implements itaskinstruction
{	
	function execute($then_that_item, $taskid, $taskinstanceid)
	{
		$should_render_required_fields = ($then_that_item["render_required_fields"] == "true");
		
		if ($then_that_item["linkparenttochild"] == "false")
		{
			$taskid = "";
			$taskinstanceid = "";
			$state = "STARTED";
			$inputparameters = array();
		}
		else
		{	
			$instancemeta = tasks::gettaskinstance($taskid, $taskinstanceid);
			$state = $instancemeta["state"];
			$inputparameters = $instancemeta["inputparameters"];
		}
			
		$happyflow_behaviour = $then_that_item["happyflow_behaviour"];
		
		// fallback
		if (!tasks::isheadless())
		{
			if ($happyflow_behaviour == "")
			{
				$happyflow_behaviour = "show_link_to_childinstance";
			}
		}
		
		$happyflow_behaviour_items = explode(";", $happyflow_behaviour);
		
		if ($then_that_item["allowdaemonchild"] == "true")
		{
			if ($taskinstanceid == "")
			{
				// fake it :)
				$state = "STARTED";
			}
		}
		
		if ($state == "STARTED")
		{
			// replace placeholders in values of atts
			foreach ($then_that_item as $key => $val)
			{
				if (is_string($key) && is_string($val))
				{
					if (functions::stringcontains($val, "{{") && functions::stringcontains($val, "}}"))
					{
						$then_that_item[$key] = functions::translatesingle($val, "{{", "}}", $inputparameters);
					}
				}
			}
			
			$create_taskid = $then_that_item["create_taskid"];
			// apply inputparameters
			$create_taskid = functions::translatesingle($create_taskid, "{{", "}}", $inputparameters);
			
			// 
			$extraparameters = array
			(
				"create_taskid" => $then_that_item["create_taskid"],
			);
			$create_taskid = functions::translatesingle($create_taskid, "{{", "}}", $extraparameters);
			
			if (functions::stringcontains($create_taskid, "{"))
			{
				$m = "nxs_task_instance_do_create_task_instance; create_taskid still has unreplaced placeholders; $create_taskid";
				if (tasks::isheadless())
				{
					$result["nackdetails"] = $m;
					$result["result"] = "NACK";
					return $result;
				}
				else
				{
					$result["console"][] = "<span style='background-color: red; color: white;'>NACK; $m</span>";
					$result["result"] = "OK";
				return $result;
				}
			}
			
			$tasktitle = tasks::gettasktitle($create_taskid);
			$processingtype = tasks::getprocessingtype($create_taskid);
			$result["console"][] = "CREATING TASK INSTANCE {$create_taskid}; $tasktitle ($processingtype)";
			
			$parameters = array();
			
			$add_taskinstance_parameters = $then_that_item["add_taskinstance_parameters"];
			
			if (!tasks::isheadless())
			{
				// if in the GUI, allow 
				$parameters = $then_that_item;
				unset($parameters["type"]);
				unset($parameters["create_taskid"]);
			}
			
			foreach ($add_taskinstance_parameters as $add_taskinstance_parameter)
			{
				$parameter_name = $add_taskinstance_parameter["parameter_name"];
				$parameter_value_function = $add_taskinstance_parameter["parameter_value_function"];
				if (false)
				{
				}
				else if ($parameter_value_function == "get_taskinstance_inputparameter_value")
				{
					$k3 = $add_taskinstance_parameter["get_taskinstance_inputparameter_key"];
					$value = $inputparameters[$k3];
					$parameters[$parameter_name] = $value;
				}
				else
				{
					$result = array
					(
						"result" => "NACK",
						"error" => "unsupported parameter_value_function; $parameter_value_function",
						"wrong_config_1" => $add_taskinstance_parameter,
						"wrong_config_2" => $add_taskinstance_parameters,
						"wrong_config_3" => $then_that_item,
						
					);
					return $result;
				}
			}
			
			$clone_inputparameters = $then_that_item["clone_inputparameters"];
			if ($clone_inputparameters == "*")
			{
				foreach ($inputparameters as $k => $v)
				{
					$parameters[$k] = $v;
				}
			}
			else
			{
				if (!isset($clone_inputparameters))
				{
					// do nothing
				}
				else if (is_string($clone_inputparameters))
				{
					$clone_inputparameters_pieces = explode(";", $clone_inputparameters);
					foreach ($clone_inputparameters_pieces as $k1)
					{
						$value = $inputparameters[$k1];
						$parameters[$k1] = $value;
					}
				}
				else if (is_array($clone_inputparameters))
				{
					foreach ($clone_inputparameters as $k1)
					{
						$value = $inputparameters[$k1];
						$parameters[$k1] = $value;
					}
				}
				else
				{
					$result = array
					(
						"result" => "NACK",
						"error" => "unsupported clone_inputparameters value?",
					);
					return $result;
				}
			}
			
			$static_inputparameters = $then_that_item["static_inputparameters"];
			if (is_array($static_inputparameters))
			foreach ($static_inputparameters as $k3 => $v3)
			{
				$parameters[$k3] = $v3;
			}

			$doit = false;
			if (tasks::isheadless())
			{
				$doit = true;
			}
			else
			{
				$enabler = md5(json_encode($args));
				if ($_REQUEST["doit"] == $enabler)
				{
					$doit = true;
				}
			}
			
			if ($doit)
			{
				$action_result = tasks::createtaskinstance($create_taskid, "", $taskid, $taskinstanceid, "", $parameters);
				
				/*
				$result["console"][] = "CREATING TASK INSTANCE; $action_url";
				$result["console"][] = "SUCCESFULLY CREATED TASK INSTANCE; $action_string";
				*/
				
				foreach ($happyflow_behaviour_items as $happyflow_behaviour_item)
				{
					if (false)
					{
						//
					}
					else if ($happyflow_behaviour_item == "")
					{
						//
					}
					else if ($happyflow_behaviour_item == "end_task_instance")
					{
						$result["console"][] = "happyflow_behaviour_item; ENDING TASK INSTANCE";
						//
						$sub_action_url = "https://global.nexusthemes.com/api/1/prod/end-task-instance/?nxs=businessprocess-api&nxs_json_output_format=prettyprint&businessprocesstaskid={$taskid}&instance_context={$taskinstanceid}";
						$sub_action_string = file_get_contents($sub_action_url);
						$sub_action_result = json_decode($sub_action_string, true);
						if ($sub_action_result["result"] != "OK") 
						{
							$result = array
							(
								"result" => "NACK",
								"details" => "unable to fetch action_url; $action_url",
							);
							return $result;
						}
					
						$result["console"][] = "happyflow_behaviour_item; TASK INSTANCE ENDED";
					}
					else if ($happyflow_behaviour_item == "reload_gui_current_page")
					{
						$marker = $then_that_item["marker"];
						$random_reload_gui_token = time();
					$currenturl = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={$taskid}&taskinstanceid={$taskinstanceid}&random_reload_gui_token={$random_reload_gui_token}#{$marker}";
					$result["console"][] = "<script>window.location.href = '{$currenturl}';</script>";
					}
					else if ($happyflow_behaviour_item == "redirect_to_instances_overview")
					{
						$currenturl = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancelist&taskid={$taskid}";
					$result["console"][] = "<script>window.location.href = '{$currenturl}';</script>";
					}
					else if ($happyflow_behaviour_item == "redirect_to_workqueue")
					{
						$currenturl = "https://global.nexusthemes.com/?nxs=task-gui&page=workqueue";
					$result["console"][] = "<script>window.location.href = '{$currenturl}';</script>";
					}
					else if ($happyflow_behaviour_item == "start_child_task_instance")
					{
						$result["console"][] = "happyflow_behaviour_item; STARTING CHILD TASK INSTANCE";
						
						$child_taskid = $create_taskid;
						$child_taskinstanceid = $action_result["taskinstanceid"];
						$assignedtoemployee_id = nxs_task_gui_getemployeeidcurrentuser();
						
						//
						$sub_action_url = "https://global.nexusthemes.com/api/1/prod/start-task-instance/?nxs=businessprocess-api&nxs_json_output_format=prettyprint&businessprocesstaskid={$child_taskid}&instance_context={$child_taskinstanceid}&assignedtoemployee_id={$assignedtoemployee_id}";
						$sub_action_string = file_get_contents($sub_action_url);
						$sub_action_result = json_decode($sub_action_string, true);
						if ($sub_action_result["result"] != "OK") 
						{
							$result = array
							(
								"result" => "NACK",
								"details" => "unable to fetch action_url; $action_url",
							);
							return $result;
						}
					
						$result["console"][] = "happyflow_behaviour_item; CHILD TASK INSTANCE STARTED";
					}
					else if ($happyflow_behaviour_item == "redirect_to_child_instance")
					{
						$child_taskid = $create_taskid;
						$child_taskinstanceid = $action_result["taskinstanceid"];
						
						$currenturl = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={$child_taskid}&taskinstanceid={$child_taskinstanceid}";
					$result["console"][] = "<script>window.location.href = '{$currenturl}';</script>";
					}
					else
					{
						$result = array
						(
							"result" => "NACK",
							"error" => "unsupported happyflow_behaviour_item; $happyflow_behaviour_item",
						);
						return $result;
					}
				}
				
				/*
				if (!tasks::isheadless())
				{
					// reload page
					$marker = $then_that_item["marker"];
					$random_reload_gui_token = time();
					$currenturl = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={$taskid}&taskinstanceid={$taskinstanceid}&random_reload_gui_token={$random_reload_gui_token}#{$marker}";
					$result["console"][] = "<script>window.location.href = '{$currenturl}';</script>";
					$result["console"][] = "About to redirect ... ";
				}
				*/
			}
			else
			{
				$more = "";
				foreach ($happyflow_behaviour_items as $happyflow_behaviour_item)
				{
					if (false)
					{
						//
					}
					else if ($happyflow_behaviour_item == "")
					{
						//
					}
					else if ($happyflow_behaviour_item == "end_task_instance")
					{
						$more .= " and end the current instance ";
					}
					else if ($happyflow_behaviour_item == "redirect_to_instances_overview")
					{
						$more .= " and reload to the task instances overview ";
					}
					else if ($happyflow_behaviour_item == "reload_gui_current_page")
					{
						$more .= " and reload the current instance ";
					}
					else if ($happyflow_behaviour_item == "redirect_to_child_instance")
					{
						$more .= " and redirect to the new instance ";
					}
					else if ($happyflow_behaviour_item == "start_child_task_instance")
					{
						$more .= " and start child instance ";
					}
					else
					{
						$more .= " and {$happyflow_behaviour_item} (TODO) ";
					}
				}
				
				$currenturl = functions::geturlcurrentpage();
				$action_url = $currenturl;
				$action_url = functions::addqueryparametertourl($action_url, "doit", $enabler, true, true);
				//$result["console"][] = "<span style='background-color: orange; color: white;'>NOTE: procrastinating</span> the creation of the task instance because the shortcode is invoked by the GUI (not headless through a workflow/batch)";
				//$result["console"][] = "<a href='{$action_url}#$marker'>Click here to create the instance through GUI {$more}</a>";
				
				if (true) // $should_render_required_fields)
				{
					$id = "ct_" . do_shortcode("[nxs_string ops='randomstring']");

					$html = "";
					$html .= "<div style='margin: 5px; padding: 5px; border-color: red; border-width: 1px; border-style: solid;'>";
					
					$meta = tasks::getreflectionmeta($create_taskid);
					$required_fields = $meta["required_fields"];
					$html .= "<table>";
					foreach ($required_fields as $required_field)
					{
						$correct_required_field = str_replace(".", "_", $required_field);
						if ($required_field == $correct_required_field) 
						{
							$fieldvalue = "";
							if ($parameters[$required_field] != "")
							{
								$fieldvalue = $parameters[$required_field];	
							}
							if ($static_inputparameters[$required_field] != "")
							{
								$fieldvalue = $static_inputparameters[$required_field];
							}
							
							if ($fieldvalue == "")
							{
								// consider using the value from the input parameter of this instance
								// as the initial value
								$fieldvalue = $inputparameters[$required_field];
							}
							
							$html .= "<tr>";
							$html .= "<td>{$required_field}</td>";
							$escaped_fieldvalue = $fieldvalue;
							$escaped_fieldvalue = str_replace("'", "&apos;", $escaped_fieldvalue);
							//$clipboardvalue = str_replace("&#92;", "\\", $clipboardvalue);
							//$clipboardvalue = str_replace("'", "&apos;", $clipboardvalue);
							//$clipboardvalue = str_replace("\\", "\\\\", $clipboardvalue);
							
							$html .= "<td><input type='text' name='{$id}_{$required_field}' id='{$id}_{$required_field}' style='min-width: 300px;' value='{$escaped_fieldvalue}' />";
							$html .= "</tr>";
						}
					}
					$html .= "</table>";
					
					$html .= "<style>";
					$html .= ".createbutton {";
					$html .= "  display: inline-block;";
					$html .= "  background-color: Crimson;  ";
					$html .= "  border-radius: 5px;";
					$html .= "  color: white;";
					$html .= "  padding: .5em;";
					$html .= "  text-decoration: none;";
					$html .= "}";
					$html .= "";
					$html .= ".createbutton:focus,";
					$html .= ".createbutton:hover {";
					$html .= "  background-color: FireBrick;";
					$html .= "  color: White;";
					$html .= "}				";
					$html .= "</style>";
					
					$html .= "<a class='createbutton' href='#' onclick='nxs_js_task_{$id}(); return false;'>CREATE INSTANCE</a>";
					if (count($happyflow_behaviour_items) > 0)
					{
						$html .= "<br />";
						$html .= "This will create the child instance and then:<br />";
						$html .= implode("<br />", $happyflow_behaviour_items);
						$html .= "<br />";
					}
					$html .= "<div id='okwrap_{$id}' style='display: none; background-color: green; color: white;'>";
					$html .= "DONE";
					$html .= "<div id='okwrap_output_{$id}'></div>";
					$html .= "</div>";
					
					$html .= "<br />";
					$html .= "<script>";
					$html .= "function nxs_js_task_{$id}() {";
					$html .= "console.log('todo; invoke api to create instance and act accordingly');";
					
					$html .= "var url = 'https://global.nexusthemes.com/api/1/prod/create-task-instance/';";
					
					$html .= "var request = {";
					$html .= "'nxs': 'businessprocess-api',";
					$html .= "'businessprocesstaskid': '{$create_taskid}',";
						
					$sticky = tasks::getstickyparameters();
					foreach ($parameters as $k2 => $val)
					{
						if (in_array($k2, $sticky))
						{
							// ignore these, those are sticky
							continue;
						}
						
						$escaped_val = $val;
						$escaped_val = str_replace("'", "&apos;", $escaped_val);
						
						$html .= "'{$k2}': '{$escaped_val}',";
					}
					
					$html .= "'createdby_taskid': '{$taskid}',";
					$html .= "'createdby_taskinstanceid': '{$taskinstanceid}'";
					
					$html .= "};";
					
					// add input parameters from screen
					foreach ($required_fields as $required_field)
					{
						$correct_required_field = str_replace(".", "_", $required_field);
						if ($required_field == $correct_required_field) 
						{
							$fieldid = "{$id}_{$required_field}";
							$html .= "request.{$required_field} = jQuery('#{$fieldid}').val();";
						}
						else
						{
							$errors []= "incorrect required_field; $required_field (replace dots with underscores)";
						}
					}
						
					if (functions::stringcontains($happyflow_behaviour, "end_task_instance"))
					{
						$html .= "request.endparentinstance = 'true';";
						//
					}
					
					if (functions::stringcontains($happyflow_behaviour, "start_child_task_instance"))
					{
						$html .= "request.startinstance = 'true';";
						
						// 
						$assigned_to = nxs_task_gui_getemployeeidcurrentuser();
						
						$html .= "request.assigned_to = '{$assigned_to}';";
						//
					}
					
					$html .= "console.log('parameters:');";
					$html .= "console.log(request);";
					
					// post the query
					$html .= "$.ajax({";
					$html .= "  url: url,";
					$html .= "  data: request,";
					$html .= "  dataType: 'json',";
					$html .= "  async: false,";
					$html .= "  type: 'POST'";
					$html .= "}).done(function(data) {";
					$html .= "console.log('response:');";
					$html .= "console.log(data);";
					
					$html .= "var child_taskid = data.taskid;";
					$html .= "var child_taskinstanceid = data.taskinstanceid;";
					
					$html .= "jQuery('#okwrap_{$id}').show();";
					
					if (functions::stringcontains($happyflow_behaviour, "redirect_to_child_instance"))
					{
						$html .= "var child_url = 'https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid=' + child_taskid + '&taskinstanceid=' + child_taskinstanceid;";
						$html .= "window.location.href = child_url;";
					}
					
					if (functions::stringcontains($happyflow_behaviour, "redirect_to_instances_overview"))
					{
						$currenttaskid = $_REQUEST["taskid"];
						$html .= "var url = 'https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancelist&taskid={$currenttaskid}';";
						$html .= "window.location.href = url;";
					}
					
					if (functions::stringcontains($happyflow_behaviour, "reload_gui_current_page"))
					{
						$marker = $then_that_item["marker"];
						$random_reload_gui_token = time();
						$currenturl = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={$taskid}&taskinstanceid={$taskinstanceid}&random_reload_gui_token={$random_reload_gui_token}#{$marker}";
						$html .= "window.location.href = '{$currenturl}';";
					}
					
					if (functions::stringcontains($happyflow_behaviour, "show_link_to_childinstance"))
					{
						$html .= "var child_url = 'https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid=' + child_taskid + '&taskinstanceid=' + child_taskinstanceid;";
						
						$html .= "var subhtmlstring = '<a target=\"_blank\" href=\"' + child_url + '\">open instance</a>';";
						$html .= "jQuery('#okwrap_output_{$id}').append(subhtmlstring);";
					}
					
					$html .= "});";
					
					//
					$html .= "}";
					
					
					$html .= "</script>";
					
					if (count($errors) > 0)
					{
						$errors_html = json_encode($errors);
						$html .= "<div>ERRORS; $errors_html</div>";
					}

					$html .= "</div>";
					$result["console"][] = $html;
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
}