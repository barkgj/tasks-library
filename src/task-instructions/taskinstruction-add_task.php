<?php

use barkgj\functions;
use barkgj\tasks;
use barkgj\tasks\itaskinstruction;

class addtask implements itaskinstruction
{
	function execute($then_that_item, $taskid, $taskinstanceid)
	{
		$result["console"][] = "<span id='add_task_marker'>add task marker</span";

		$instancemeta = tasks::gettaskinstance($taskid, $taskinstanceid);
		$inputparameters = $instancemeta["inputparameters"];

		$messageformat = $inputparameters["messageformat"];
		$subject_words_to_qualify = $inputparameters["subject_words_to_qualify"];
		$subject_words_to_qualify_list = explode("^", $subject_words_to_qualify);

		$inputparameters_to_clone = $inputparameters["inputparameters_to_clone"];
		$inputparameters_to_clone_list = explode("^", $inputparameters_to_clone);
		$inputparameters_to_clone_list = array_map('trim', $inputparameters_to_clone_list);
		
		$result = array();

		$prio = $then_that_item["prio"];

		$task_title_template = $then_that_item["task_title_template"];
		if ($task_title_template == "")
		{
			$result["console"][] = "err; add_task; task_title_template not set in then_that_item";
			$result["result"] = "OK";
			$result["nack_message"] = "add_task; task_title_template not set in then_that_item";

			return $result;
		}

		$task_title = nxs_filter_translatesingle($task_title_template, "{{", "}}", $inputparameters);
		$json_escaped_task_title = str_replace("\"", "\\\"", $task_title);

		// check if task exists with specified title
		$fetch_url = "https://global.nexusthemes.com/api/1/prod/get-task-by-title/?nxs=businessprocess-api&nxs_json_output_format=prettyprint";
		$fetch_url = functions::addqueryparametertourl_v2($fetch_url, "title", $task_title, true, true);
		$fetch_string = file_get_contents($fetch_url);
		$fetch_result = json_decode($fetch_string, true);
		if ($fetch_result["result"] != "OK") 
		{
			$result["console"][] = "err; fetch_url error";
			$result["result"] = "OK";
			$result["nack_message"] = "fetch_url; $fetch_url; fetch error";

			return $result;
		}
	
		if ($fetch_result["found"] == false)
		{
			// if it does NOT exist
			$result["console"][] = "the task to handle this scenario (title: '$task_title') does not yet exist";

			//  check if 'open' task instance exists to create task instance to create new task
			$args_json = '{"if_this":{"type":"true_if_each_subcondition_is_true","subconditions":[{"type":"true_if_task_has_required_taskid","required_taskid":"64"},{"type":"true_if_inputparameter_has_required_value_for_key","key":"title","required_value":"{{TITLE}}"},{"type":"true_if_in_any_of_the_required_states","any_of_the_required_states":["CREATED","STARTED"]}]}}';
			$args_json = str_replace("{{TITLE}}", $json_escaped_task_title, $args_json);
		
			$fetch_url = "https://global.nexusthemes.com/api/1/prod/search-task-instances/?nxs=businessprocess-api&nxs_json_output_format=prettyprint";
			$fetch_url = nxs_addqueryparametertourl_v2($fetch_url, "args_json", $args_json, true, true);
			//$result["console"][] = "<a target='_blank' href='$fetch_url'>debug search-task-instances; $fetch_url</a>";
			$fetch_string = file_get_contents($fetch_url);
			$fetch_result = json_decode($fetch_string, true);
			if ($fetch_result["result"] != "OK") 
			{
				$result["console"][] = "err; fetch_url error; {$fetch_url}";
				$result["result"] = "OK";
				$result["nack_message"] = "fetch_url; $fetch_url; fetch error";

				return $result;
			}
			$opentaskinstanceexists = ($fetch_result["evaluations"]["count"] > 0);
			if (!$opentaskinstanceexists)
			{
			//  if task instance exists does NOT to create task instance to create new task
			$result["console"][] = "its also not scheduled to be created (no task instance was found to create the task)";
			
			if ($_REQUEST["confirmed"] == "")
			{
				// ask user to confirm to create task instance to create new task
				$result["console"][] = "please confirm to create task instance to create task";
				$urlcurrentpage = nxs_geturlcurrentpage();
				$confirm_url = $urlcurrentpage;
				$confirm_url = nxs_addqueryparametertourl_v2($confirm_url, "confirmed", "true", true, true);
				$result["console"][] = "* <a href='{$confirm_url}#thanksconfirmation'>click here to confirm</a>";
			}
			else
			{
					// if user confirms
					$result["console"][] = "<span id='thanksconfirmation'>thanks for confirming the task instance should be created</span>";
				
					// create task instance to create new task
					$action_url = "https://global.nexusthemes.com/api/1/prod/create-task-instance/?nxs=businessprocess-api&nxs_json_output_format=prettyprint";
					$action_url = nxs_addqueryparametertourl_v2($action_url, "createdby_taskid", $taskid, true, true);
					$action_url = nxs_addqueryparametertourl_v2($action_url, "createdby_taskinstanceid", $taskinstanceid, true, true);
					
					$action_url = nxs_addqueryparametertourl_v2($action_url, "taskid", "64", true, true);
					$action_url = nxs_addqueryparametertourl_v2($action_url, "requirements", "", true, true);
					$action_url = nxs_addqueryparametertourl_v2($action_url, "title", $task_title, true, true);
					$action_url = nxs_addqueryparametertourl_v2($action_url, "recursatregularinterval", "no", true, true);
					$action_url = nxs_addqueryparametertourl_v2($action_url, "prio", $prio, true, true);
					
					$action_string = file_get_contents($action_url);
					$action_result = json_decode($action_string, true);
					if ($action_result["result"] != "OK")
					{
						$result["console"][] = "action_url error";
						$result["result"] = "OK";
						$result["nack_message"] = "action_url; $action_url; error";
						
						return $result;
					}
					$result["console"][] = "<span style='background-color: #0F0;'>task instance created</span>";
					
					$urlcurrentpage = nxs_geturlcurrentpage();
					$next_url = $urlcurrentpage;
						$next_url = nxs_addqueryparametertourl_v2($next_url, "t", time(), true, true);
					$next_url = "{$next_url}#finishcreationtask";	// refreshes current page
					$result["console"][] = "<a href='{$next_url}'>click here to proceed with the next step (b)</a>";
				}
			}
			else
			{
				$result["console"][] = "the following open task instance(s) exist:";
				
			// if task instance DOES exists to create task instance to create new task
			foreach ($fetch_result["matches"]["taskinstances"] as $instance)
			{
				$currenttaskid = $instance["taskid"];
				$currenttaskinstanceid = $instance["taskinstanceid"];
				$url = $instance["url"];
				$result["console"][] = "<a id='finishcreationtask' target='_blank' href='{$url}'>click here to finish the creation of the task ({$currenttaskinstanceid}@{$currenttaskid})</a><br />";
				
				$urlcurrentpage = nxs_geturlcurrentpage();
				$next_url = $urlcurrentpage;
					$next_url = nxs_addqueryparametertourl_v2($next_url, "t", time(), true, true);
				$next_url = "{$next_url}#extendworkflows";	// refreshes current page
				$result["console"][] = "<a href='{$next_url}'>next step (extending the workflows/extend .txt file)</a>";
			}
			}
		}
		else
		{
			$result["console"][] = "task exists; " . json_encode($fetch_result);
			
			if (false)
			{
			}
			else if ($messageformat == "STRUCTURED")
			{
				// checks
				if (true)
				{
					/*
					if ($subject_words_to_qualify == "")
					{
						$result["console"][] = "<span style='background-color: red; color: white;'>error; subject_words_to_qualify not filled in</span>";
						$result["result"] = "OK";
						return $result;
					}
					
					if ($inputparameters_to_clone == "")
					{
						$result["console"][] = "<span style='background-color: red; color: white;'>error; inputparameters_to_clone not filled in</span>";
						$result["result"] = "OK";
						return $result;
					}
					*/
				}

			//  get the taskid of that task
			$taskid_to_handle = $fetch_result["props"]["nxs.p001.businessprocess.task_id"];
		
			//  instruct user to modify workflow such that that task will be created
			$result["console"][] = "<span id='extendworkflows'>extend workflows</a>";
			$result["console"][] = "* open /metamodel/task.recipes/{$taskid}.workflows.json with FTP";
			$result["console"][] = "* alternative flow; if the workflows file does not yet exist";
			$result["console"][] = "** create workflows file (todo: this should be part of the process to create a new task; it should have a step to install a default workflow that does nothing)";
			
			$html = <<<EOD
		<br />
		&#91;
		{
			"title": "start created task instance",
			"if_this": {
			"type": "true_if_taskinstance_has_state",
			"required_state": "CREATED"
			},
			"then_that_items": [
			{
				"type": "start_task_instance"
			}
			]
		}
		&#93;<br />
		<br />
		EOD;
					
				
				
				$result["console"][] = "<div style='margin-left: 150px; background-color: #ddd; font-family: courier; font-style: italic; padding: 10px;'>{$html}</div>";
				$result["console"][] = "* append the following workflow to the bottom of the json file, just BEFORE the last closing '&#93;' character:";
				
				$template = "";
				$template .= "<div style='background-color: #ddd; padding: 10px; margin-left: 150px; font-size:12px;font-style:italic; font-family: courier;'>";
				$template .= <<<EOD
						,
					{
						"title": "{$json_escaped_task_title}",
						"if_this": {
						"type": "true_if_each_subcondition_is_true",
						"subconditions": [
							{
							"type": "true_if_taskinstance_has_state",
							"required_state": "STARTED"
							},
							{
							"type": "true_if_inputparameter_contains_each_word",
							"inputparameter": "subject_original_ticket",
							"words": [
		EOD;
			
					$quote = '"';
					$template .= "{$quote}".implode("{$quote}<br />,{$quote}", $subject_words_to_qualify_list)."{$quote}<br />";
			
					$template .= <<<EOD
						]
						}
					]
					},
					"then_that_items": [
		EOD;
			
					// optional part; to get variable from subject in between
					$var_1_input = $inputparameters["var_1_input"];
					$var_1_name = $inputparameters["var_1_name"];
					$var_1_before = $inputparameters["var_1_before"];
					$var_1_after = $inputparameters["var_1_after"];
					if ($var_1_input != "" && $var_1_name != "" && $var_1_before != "" && $var_1_after != "")
					{
						$template .= 
		<<<EOD
							{
							"type": "translate_inputparameter_to_inputparameter",
							"source_name": "{$var_1_input}",
							"translate_functions": [
								{
								"type": "get_value_between",
								"start": "{$var_1_before}",
								"end": "{$var_1_after}"
								}
							],
							"destination_name": "{$var_1_name}"
							},
		EOD;
					}
			
					// optional part; to get variable from subject after
					$var_1_grabafter_seperator = $inputparameters["var_1_grabafter_seperator"];
					$var_1_grabafter_name = $inputparameters["var_1_grabafter_name"];
					if ($var_1_grabafter_seperator != "" && $var_1_grabafter_name != "")
					{
						$template .= <<<EOD
							{
							"type": "translate_inputparameter_to_inputparameter",
							"source_name": "subject_original_ticket",
							"translate_functions": [
								{
								"type": "get_value_after",
								"seperator": "{$var_1_grabafter_seperator}"
								}
							],
							"destination_name": "{$var_1_grabafter_name}"
							},
		EOD;
					}
			
					$template .= <<<EOD
				{
					"type": "create_task_instance",
					"create_taskid": "{$taskid_to_handle}",
							"clone_inputparameters": 
							[
		EOD;
			
					$quote = '"';
					$template .= "{$quote}".implode("{$quote}<br />,{$quote}", $inputparameters_to_clone_list)."{$quote}<br />";
			
					$template .= 
		<<<EOD
							]
				},
				{
					"type": "end_task_instance"
				}
				]
			}
		EOD;
			
				$template .= "</div>";
				$template = str_replace("\r\n", "<br />", $template);
				$template = str_replace("\t", "&nbsp;&nbsp;", $template);
				
				$result["console"][] = $template;
				
					$result["console"][] = "* Improve the markup of the json and ensure its valid";
					$result["console"][] = "** <a target='_blank' href='http://jsoneditoronline.org/'>http://jsoneditoronline.org</a>";
					$result["console"][] = "** Paste the json";
					$result["console"][] = "** Push the button to parse the json (from left to right)";
					$result["console"][] = "*** If the GUI complains its invalid";
					$result["console"][] = "**** Fix the invalid parts";
					$result["console"][] = "** Push the button to parse the json (from right to left; this improves the markup/layout)";
					$result["console"][] = "** Copy the content on the left side to clipboard";
					$result["console"][] = "** Paste the clipboard to the json workflows file (locally)";
					$result["console"][] = "** Save the json workflows file (locally)";
				$result["console"][] = "* Override /metamodel/task.recipes/{$taskid}.workflows.json with FTP";
					$result["console"][] = "** FTP the updated workflows file to the server";
					$result["console"][] = do_shortcode("*** [nxs_p001_task_instruction type='render-copy-to-clipboard' label='Source folder:' value='C:&#92;Proj&#92;internal-v2&#92;projects&#92;amazon&#92;metamodel&#92;task.recipes']");
					$result["console"][] = "*** Filename: {{taskid}}.workflows.json";
					$result["console"][] = "*** Destination folder: <span style='font-family: courier;'>/metamodel/task.recipes</span>";
				}
				else if ($messageformat == "UNSTRUCTURED")
				{
					// add additional quality checks here as needed in the future
				}
				else
				{
					$result["console"][] = "unsupported messageformat; $messageformat";
				}
		}
		
		$result["result"] = "OK";
		
		return $result;
	}
}