<?php

// to include this file, use:
// require_once("/srv/generic/libraries-available/nxs-tasks/nxs-tasks.php");

//require_once("/srv/generic/libraries-available/nxs-fs/nxs-fs.php");
//$result = __DIR__ . "/task-instructions/taskinstruction-{$type}.php";

namespace barkgj\tasks;

require "itaskinstruction.php";

use barkgj\functions;
use barkgj\datasink\entity;
use barkgj\tasks\itaskinstruction;
use barkgj\tasks\taskinstruction;

final class tasks
{
	public static function gettaskrecipepath($taskid)
	{
		$result = functions::getsitedatafolder() . "/tasks-recipes/{$taskid}.txt";
		return $result;
	}

	public static function gettaskinstructionpath($taskinstructiontype)
	{
		$result = __DIR__ . DIRECTORY_SEPARATOR . "task-instructions" . DIRECTORY_SEPARATOR . "taskinstruction-{$taskinstructiontype}.php";
		return $result;
	}

	public static function ensuretaskinstructionloaded($taskinstructiontype)
	{
		$path = tasks::gettaskinstructionpath($taskinstructiontype);
		if (file_exists($path))
		{
			require_once($path);
		}
		else
		{
			functions::throw_nack("taskinstruction not loaded; {$taskinstructiontype}; path not found; {$path}");
		}
		echo "ensuretaskinstructionloaded; loaded $path;";
	}

	public static function taskexists($taskid)
	{
		$existsargs = array
		(
			"datasink_realm" => "tasks",
			"datasink_entitytype" => "task",
			"id" => $taskid
		);
		$r = entity::entityexists($existsargs);
		return $r;
	}

	public static function gettaskmeta($taskid)
	{
		$result = array();

		$taskrecipepath = tasks::gettaskrecipepath($taskid);
		$result["recipe"]["path"] = $taskrecipepath;
		if (file_exists($taskrecipepath))
		{
			$result["recipe"]["size"] = filesize($taskrecipepath);
			$result["recipe"]["isfound"] = true;
			$result["recipe"]["content"] = file_get_contents($taskrecipepath);
		}
		else
		{
			$result["recipe"]["isfound"] = false;
		}

		$getentitymetadatarawargs = array
		(
			"datasink_realm" => "tasks",
			"datasink_entitytype" => "task",
			"id" => $taskid
		);
		$entitymetadataraw = entity::getentitymetadataraw($getentitymetadatarawargs);
		$result["task"]["metadataraw"] = $entitymetadataraw;

		$instancescount = tasks::gettaskinstancescount($taskid);
		$result["taskinstances"]["count"] = $instancescount;

		return $result;
	}

	public static function taskinstanceexists($taskid, $taskinstanceid)
	{
		$existsargs = array
		(
			"datasink_realm" => "tasks",
			"datasink_entitytype" => "task_{$taskid}_instances",
			"id" => $taskinstanceid
		);
		$r = entity::entityexists($existsargs);
		return $r;
	}

	public static function createtaskinstance($taskid, $assigned_to, $createdby_taskid, $createdby_taskinstanceid, $mail_assignee, $stateparameters)
	{
		if ($taskid == "") { functions::throw_nack("taskid not set"); }
		
		// filter out unwanted stateparameters for the child, even if those are specified
		$unwantedfields = array("allowdaemonchild", "render_required_fields", "happyflow_behaviour", "linkparenttochild", "assigned_to", "cond_wrap_state_if_needsoffspringintance");
		foreach ($unwantedfields as $unwantedfield)
		{
			unset($stateparameters[$unwantedfield]);
		}
		
		// fetch task to ensure the task exists
		if (!tasks::taskexists($taskid)) { functions::throw_nack("invalid task (id {$taskid} not found)"); }
		
		$taskinstanceid = functions::create_guid();
		if (tasks::taskinstanceexists($taskid, $taskinstanceid)) { functions::throw_nack("taskinstance already exists"); }
		
		$parentstateparameters = array();
		if ($createdby_taskid != "" && $createdby_taskinstanceid != "")
		{
			$parentstateparameters = tasks::gettaskinstancestateparameters($createdby_taskid, $createdby_taskinstanceid);
			
			// handle sticky_stateparameters; these will automatically be copied to the offspring,
			// if they have a value, and if not yet "set" manually
			$sticky_stateparameters = tasks::getstickyparameters();
			foreach ($sticky_stateparameters as $key)
			{
				if (!isset($stateparameters[$key]))
				{
					$parentvalue = $parentstateparameters[$key];
					if ($parentvalue != "")
					{
						$stateparameters[$key] = $parentvalue;
					}
					else
					{
						// no need to set empty var
					}
				}
				else
				{
					// if the invoker already explicitly specified a value, use that one and don't use the sticky one
				}
			}
		}
		else
		{
			// if this new instance has no parent, we cannot apply sticky parameters
		}
		
		$storeargs = array
		(
			"datasink_invokedbytaskid" => $createdby_taskid,
            "datasink_invokedbytaskinstanceid" => $createdby_taskinstanceid,
			"datasink_realm" => "tasks",
			"datasink_entitytype" => "task_{$taskid}_instances",
            "id" => $taskinstanceid,
			"state" => "CREATED",
			"createtime" => time(),
			"createdbyip" => $_SERVER['REMOTE_ADDR'],
			"createdby_taskid" => $createdby_taskid,
			"createdby_taskinstanceid" => $createdby_taskinstanceid,
			"instance_context" => $taskinstanceid,	// obsolete, but in here for downwards compatibility
			"assignedtouserid" => $assigned_to,
			"stateparameters" => $stateparameters,
		);

		$r = entity::storeentitydata($storeargs);
		
		$result = array
		(
			"result" => "OK",
			"taskid" => $taskid,
			"taskinstanceid" => $taskinstanceid,
		);
		
		if ($createdby_taskid != "" && $createdby_taskinstanceid != "")
		{
			// update the forked_tasks with the task instance we just created
			tasks::appendcreatedtask_to_taskinstance($createdby_taskid, $createdby_taskinstanceid, $taskid, $taskinstanceid);
		}
		else
		{
			error_log("create-task-instance-api; warning; created $taskid $taskinstanceid - no createdby_taskid and/or createdby_taskinstanceid given");
		}
		
		/*
		if ($mail_assignee != "")
		{
			if ($assigned_to != "")
			{
				$mailtemplate = 83;
				$mail_url = "https://global.nexusthemes.com/api/1/prod/send-mail-template-for-employee/?nxs=mail-api&json_output_format=prettyprint&employeeid={$assigned_to}&mailtemplate={$mailtemplate}";
				
				$mail_url = addqueryparametertourl_v2($mail_url, "tasktitle", $tasktitle, true, true);
				
				$taskinstance_url = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={$taskid}&taskinstanceid={$taskinstanceid}";
				$mail_url = addqueryparametertourl_v2($mail_url, "taskinstance_url", $taskinstance_url, true, true);
				
				$mail_string = file_get_contents($mail_url);
				$mail_result = json_decode($mail_string, true);
				$result["mail_assignee_url"] = $mail_url;
				$result["mail_assignee_result"] = $mail_result;
			}
			else
			{
				error_log("businessprocessapiimpl; mail_assignee set, assigned_to is empty?");
			}
		}
		*/
		
		return $result;
	}

	public static function isheadless()
	{
		$result = true;
		
		if (false)
		{
		}
		else if (!function_exists("tasks_gui_getsession"))
		{
			$result = true;
		}
		else if ($_REQUEST["page"] == "taskinstancelist" && $_REQUEST["bulk_action"] != "")
		{
			$result = true;
		}
		else if ($_REQUEST["page"] == "batchsegmentexecutor")
		{
			$result = true;
		}
		else
		{
			/*
			$roles = tasks_gui_getrolescurrentuser();
			if ($roles == "rpa")
			{
				$result = true;
			}
			else
			{
				$result = false;
			}
			*/
			$result = false;
		}
		
		//error_log("tasks::isheadless returns:" . json_encode($result) . " for ----> " . json_encode($_REQUEST));
		
		return $result;
	}

	public static function getstickyparameters()
	{
		$overridefunction = "tasks__override__getstickyparameters";
		if (function_exists($overridefunction))
		{
			$result = call_user_func($overridefunction);
		}
		else
		{
			// TODO: these should be moved to an ixplatform table
			$result = array
			(
			);

			/*
				"message_origin",
				"facebook_origin_url",
				"original_helpscoutticketnr", 
				"helpscoutthreadid", 
				"subject_original_ticket", 
				"message", 
				"licenseid", 
				"sender_email", 
				"repliesto_taskid",
				"repliesto_taskinstanceid",
				"messagewasforked",
				"vendor_id",
				"cc",
				"attachments_json",
				"youtube_url",
				"firstname",
				"event_Records_0_ses_mail_messageId",
				"event_Records_0_ses_mail_commonHeaders_messageId",
			*/
		}
		
		return $result;
	}

	public static function gettaskinstance($taskid, $taskinstanceid)
	{
		if ($taskid == "" || $taskinstanceid == "") 
		{
			$result["isfound"] = false;
			$result["taskid"] = $taskid;
			$result["taskinstanceid"] = $taskinstanceid;
		}
		else
		{
			$getentitymetadatarawargs = array
			(
				"datasink_realm" => "tasks",
				"datasink_entitytype" => "task_{$taskid}_instances",
				"id" => $taskinstanceid,
			);
			$result = entity::getentitymetadataraw($getentitymetadatarawargs);
		}
		
		return $result;
	}

	public static function gettaskinstancestate($taskid, $taskinstanceid)
	{
		$instance = tasks::gettaskinstance($taskid, $taskinstanceid);
		$result = $instance["state"];
		return $result;
	}

	public static function gettaskinstancestateparameters($taskid, $taskinstanceid)
	{
		$instance = tasks::gettaskinstance($taskid, $taskinstanceid);
		$result = $instance["stateparameters"];
		$result["taskid"] = $taskid;
		$result["taskinstanceid"] = $taskinstanceid;
		return $result;
	}

	public static function setfinishedinstructionpointer($taskid, $taskinstanceid, $finished_instruction_pointer)
	{
		$meta = tasks::gettaskinstance($taskid, $taskinstanceid);
		$meta["finished_instruction_pointer"] = $finished_instruction_pointer;
		tasks::updatetaskinstance($taskid, $taskinstanceid, $meta);
	}

	public static function getfinishedinstructionpointer($taskid, $taskinstanceid)
	{
		$meta = tasks::gettaskinstance($taskid, $taskinstanceid);
		$result = $meta["finished_instruction_pointer"];
		return $result;
	}

	public static function gettaskinstancelookup($taskid, $taskinstanceid, $orig_args = array())
	{
		$defaults = array
		(
			"exclude_keys" => array(),
		);
		$args = wp_parse_args($orig_args, $defaults);
	
		$exclude_keys = $args["exclude_keys"];
		
		$instancemeta = tasks::gettaskinstance($taskid, $taskinstanceid);
		
		// inject all attached ("static") json_lookup_runtime parameters
		$filter_attachment_type = "";	// empty means all (alt: "runtime_json_lookup")
		$attachmentids = tasks::gettaskrecipe_attachments($taskid, $taskinstanceid, $filter_attachment_type);
		foreach ($attachmentids as $attachmentid)
		{
			if (in_array($attachmentid, $exclude_keys))
			{
				// skip this one
				continue;
			}
			
			$value = tasks::gettaskrecipe_attachment($taskid, $taskinstanceid, $attachmentid);
			$result[$attachmentid] = $value;
		}
		
		// stronger than attached lookups are the state parameters of the task instance
		$persistent_instance_fields = $instancemeta["stateparameters"];
		foreach ($persistent_instance_fields as $key => $val)
		{
			if (in_array($key, $exclude_keys))
			{
				// skip this one
				continue;
			}
			
			$result[$key] = $val;
		}
		
		// runtime parameters, available for 'free', these will override any values stored above
		$result["taskid"] = $taskid;
		$result["taskinstanceid"] = $taskinstanceid;
		
		return $result;
	}

	public static function parserecipeline($taskid, $linenr, $line)
	{
		$result = array();
		
		// locate all task instructions
		$identifyingprefix = "[p001_task_instruction ";
		if (functions::stringcontains($line, $identifyingprefix))
		{
			$result["linetype"] = "taskinstruction";
				
			// for example:
			// [p001_task_instruction type='start-task-instance']
			// * * [p001_task_instruction type='set_parameter' set_stateparameter_field='elementor_affiliate_url' function='staticvalue' value='https://link.nexusthemes.com/link/?to=premiumpluginbyid:3&channel=nt&channeldetail=prodpage&mdp={{mdp_id}}&eid={{theme_itemmeta_id}}']
			
			$identifyingprefixcount = substr_count($line, $identifyingprefix);
			if ($identifyingprefixcount > 1) { functions::throw_nack("found line with multiple shortcodes; this is not (yet) supported; taskid: $taskid; linenr: $linenr; $line"); }
			
			$pieces = explode($identifyingprefix, $line, 2);
			$task_instruction_props = trim($pieces[1]);
		
			// get rid of the last character which closes the shortcode
			$task_instruction_props = trim($task_instruction_props, "]");
			$orig_properties = shortcode_parse_atts($task_instruction_props);
			$properties = $orig_properties;
			$type = $properties["type"];
			unset($properties["type"]);
		
			$task_instruction_id = $orig_properties["id"];
			if ($task_instruction_id == "")
			{
				// most likely the id is not set; in that case derive one on the fly
				$hash_input = json_encode($orig_properties);
				$hash = md5($hash_input);
				$task_instruction_id = $hash;
			}
			
			$result["task_instruction_id"] = $task_instruction_id;
			$result["type"] = $type;
			$result["properties"] = $properties;
		}
		else
		{
			$result["linetype"] = "text";
		}
		
		return $result;
	}

	public static function getreflectionmeta($taskid, $taskinstanceid = "")
	{
		$result = array();
		
		// required fields
		if (true)
		{
			$required_fields = array();
			$task_instructions = array();
			
			// based upon the .txt files
			if (true)
			{
				$recipe = tasks::gettaskrecipe($taskid);
				
				$linenr = 0;					// first line = 1
				$instructionnr = -1;			// first index = 0
				$task_instruction_id_to_linenr_mapping = array();
				
				$lines = explode("\n", $recipe);
				foreach ($lines as $line)
				{
					$linenr++;
					
					if (functions::stringcontains($line, "require-parameter"))
					{
						$result["debug"][] = "line 1:" . $line;
						
						$pieces = explode("[", $line, 2);
						$line = trim($pieces[1]);
						
						$result["debug"][] = "line 2:" . $line;
						
						// p001_task_instruction type='require-parameter' name='vpstitle']
						$pieces = explode(" ", $line, 2);
						
						$keyvalues = $pieces[1];
						
						$result["debug"][] = "keyvalues 1:" . $keyvalues;
						
						$pieces = explode("]", $keyvalues, 2);
						$keyvalues = $pieces[0];
						
						$result["debug"][] = "keyvalues 2:" . $keyvalues;
						
						// type='require-parameter' name='vpstitle'
						$atts = shortcode_parse_atts($keyvalues);
						
						$result["debug"][] = array
						(
							"line" => $line,
							"keyvalues" => $keyvalues,
						);
						

						foreach ($atts as $key => $v)
						{
							if ($key == "name") //  && $atts["required"]=="true")
							{
								$required_fields[] = $v; // $key;
							}
						}
					}
					
					// locate task instruction in line (if any)
					$line_result = tasks::parserecipeline($taskid, $linenr, $line);
					$linetype = $line_result["linetype"];
					if (false)
					{
					}
					else if ($linetype == "taskinstruction")
					{
						$taskmeta = tasks::gettaskmeta($taskid);
						$execution_pointers_support = $taskmeta["execution_pointers_support"];
					
						if ($execution_pointers_support == "v1")
						{
							// for example:
							// [p001_task_instruction type='start-task-instance']
							// * * [p001_task_instruction type='set_parameter' set_stateparameter_field='elementor_affiliate_url' function='staticvalue' value='https://link.nexusthemes.com/link/?to=premiumpluginbyid:3&channel=nt&channeldetail=prodpage&mdp={{mdp_id}}&eid={{theme_itemmeta_id}}']
		
							$instructionnr++;
							
							$task_instruction_id = $line_result["task_instruction_id"];
							$type = $line_result["type"];
							$properties = $line_result["properties"];
							//
							if (array_key_exists($task_instruction_id, $task_instruction_id_to_linenr_mapping))
							{
								$previous_linenr = $task_instruction_id_to_linenr_mapping[$task_instruction_id];
								$lookslike = json_encode($properties);
								
								functions::throw_nack("found identical task_instruction_id (shortcode) twice in the task recipe; the taskinstruction at line $line was also identically used at line $previous_linenr in task $taskid; each one must be unique; add an additional attribute to the shortcode to resolve this issue. Instruction looks like so; $type props: $lookslike ($task_instruction_id)");
							}
							$task_instruction_id_to_linenr_mapping[$task_instruction_id] = $linenr;
							
							// for example type='start-task-instance']
							
							// add it to the list of task instructions
							$result["task_instructions"][] = array
							(
								"id" => $task_instruction_id,
								"linenr" => $linenr,
								"instructionnr" => $instructionnr,
								"type" => $type,
								"attributes" => $properties,
							);
						
							// add it to dictionary of task instructions identified by its id
							$result["task_instructions_by_id"][$task_instruction_id] = array
							(
								"id" => $task_instruction_id,
								"linenr" => $linenr,
								"instructionnr" => $instructionnr,
								"type" => $type,
								"attributes" => $properties,
							);
						}
						else
						{
							// not supported
						}
					}
					else if ($linetype == "text")
					{
						// nothing to do here
					}
					else
					{
						functions::throw_nack("unsupported linetype; $linetype");	
					}
				}
			}
			
			$filter_type = "";
			$attachments = tasks::gettaskrecipe_attachments($taskid, $taskinstanceid, $filter_type);
			$result["attachment_key"] = $attachments;
			foreach ($attachments as $attachmentid)
			{
				$result["attachments"][$attachmentid] = tasks::gettaskrecipe_attachment($taskid, $taskinstanceid, $attachmentid);
			}
			
			// based upon the workflows
			$result["required_fields"] = $required_fields;
			
			// 
			
			
		}
		
		
		
		unset($result["debug"]);
		
		return $result;
	}

	public static function updatetaskinstance($taskid, $taskinstanceid, $taskinstancemeta)
	{
		if ($taskid == "") { functions::throw_nack("taskid not set"); }
		if ($taskinstanceid == "") { functions::throw_nack("taskinstanceid not set"); }
		if ($taskinstancemeta == "") { functions::throw_nack("taskinstancemeta not set"); }
		if ($taskinstancemeta["state"] == "") { functions::throw_nack("taskinstancemeta has no state?"); }

		// pull existing props
		$existingtaskinstancemeta = tasks::gettaskinstance($taskid, $taskinstanceid);
		$storeargs = $taskinstancemeta;

		$props_to_keep = array("createtime", "createdbyip", "createdby_taskid", "createdby_taskinstanceid", "instance_context", "id", "datasink_invokedbytaskid", "datasink_invokedbytaskinstanceid'");
		foreach ($props_to_keep as $prop_to_keep)
		{
			if (isset($existingtaskinstancemeta[$prop_to_keep]))
			{
				$storeargs[$prop_to_keep] = $existingtaskinstancemeta[$prop_to_keep];
			}
		}

		// 
		$storeargs["datasink_realm"] = "tasks";
		$storeargs["datasink_entitytype"] = "task_{$taskid}_instances";
		$storeargs["datasink_alreadyfoundbehaviour"] = "OVERRIDE";
		
		$r = entity::storeentitydata($storeargs);
		$result["storeentitydataresult"] = $r;
		return $result;
	}

	public static function appendstateparameter_for_taskinstance($taskid, $taskinstanceid, $key, $val)
	{
		if ($key == "")
		{
			functions::throw_nack("tasks::appendstateparameter_for_taskinstance; key not set (for $taskid, $taskinstanceid, $key, $val)");
		}
		$stateparameterstoappend = array();
		$stateparameterstoappend[$key] = $val;
		tasks::appendstateparameters_for_taskinstance($taskid, $taskinstanceid, $stateparameterstoappend);
	}

	public static function appendstateparameters_for_taskinstance($taskid, $taskinstanceid, $stateparameterstoappend)
	{
		//
		$meta = tasks::gettaskinstance($taskid, $taskinstanceid);
		
		foreach ($stateparameterstoappend as $name => $value)
		{
			$meta["stateparameters"][$name] = $value;
		}
		
		$result = tasks::updatetaskinstance($taskid, $taskinstanceid, $meta);

		return $result;
	}

	public static function gettasktitle($taskid)
	{
		$getentitymetadatarawargs = array
		(
			"datasink_realm" => "tasks",
			"datasink_entitytype" => "task",
			"id" => $taskid
		);
		$task_props = entity::getentitymetadataraw($getentitymetadatarawargs);
		$result = $task_props["title"];
		
		return $result;
	}

	// aka process_type
	public static function getprocessingtype($taskid)
	{
		$getentitymetadatarawargs = array
		(
			"datasink_realm" => "tasks",
			"datasink_entitytype" => "task",
			"id" => $taskid
		);
		$task_props = entity::getentitymetadataraw($getentitymetadatarawargs);
		$result = $task_props["processing_type"];
	}

	//
	public static function getstoredtaskinstructionresult($taskid, $taskinstanceid, $taskinstructiontype)
	{
		if ($taskid == "") { functions::throw_nack("taskid not set"); }
		if ($taskinstanceid == "") { functions::throw_nack("taskinstanceid not set"); }
		if ($taskinstructiontype == "") { functions::throw_nack("taskinstructiontype not set"); }
		
		$key = "taskinstructionresult_{$taskinstructiontype}";
		$stateparameters = tasks::gettaskinstancestateparameters($taskid, $taskinstanceid);
		$json = $stateparameters[$key];
		if ($json == "")
		{
			$result = "";
		}
		else
		{
			$result = json_decode($json, true);
		}
		return $result;
	}

	public static function storetaskinstructionresult($taskid, $taskinstanceid, $taskinstructiontype, $taskinstructionresult)
	{
		if ($taskid == "") { functions::throw_nack("taskid not set"); }
		if ($taskinstanceid == "") { functions::throw_nack("taskinstanceid not set"); }
		if ($taskinstructiontype == "") { functions::throw_nack("taskinstructiontype not set"); }
		if ($taskinstructionresult == "") { functions::throw_nack("taskinstructionresult not set"); }
		$key = "taskinstructionresult_{$taskinstructiontype}";
		$val = json_encode($taskinstructionresult);
		tasks::appendstateparameter_for_taskinstance($taskid, $taskinstanceid, $key, $val);
	}

	public static function deletestateparameters_for_taskinstance($taskid, $taskinstanceid, $stateparameters)
	{
		$instance = tasks::gettaskinstance($taskid, $taskinstanceid);
		foreach ($stateparameters as $stateparameter)
		{
			unset($instance["stateparameters"][$stateparameter]);
		}

		$result = tasks::updatetaskinstance($taskid, $taskinstanceid, $instance);
		
		return $result;
	}

	public static function deletestateparameter_for_taskinstance($taskid, $taskinstanceid, $stateparameter)
	{
		$stateparameters = array($stateparameter);
		$result = tasks::deletestateparameters_for_taskinstance($taskid, $taskinstanceid, $stateparameters);
		return $result;
	}

	public static function appendcreatedtask_to_taskinstance($taskid, $taskinstanceid, $created_taskid, $created_taskinstanceid)
	{
		$parent_taskmeta = tasks::gettaskinstance($taskid, $taskinstanceid);

		$created_task = array
		(
			"creation_time" => time(),
			"taskid" => $created_taskid,
			"taskinstanceid" => $created_taskinstanceid,
		);
		$parent_taskmeta[$taskinstanceid]["created_tasks"][] = $created_task;

		$result = tasks::updatetaskinstance($taskid, $taskinstanceid, $parent_taskmeta);
		
		return $result;
	}
	
	public static function gettaskrecipe($taskid)
	{
		$path = tasks::gettaskrecipepath($taskid);
		$recipe = file_get_contents($path);
		
		// only return the part before the first "~~~"
		$recipe_pieces = explode("~~~", $recipe);
		$recipe = $recipe_pieces[0];
		
		return $recipe;
	}

	public static function gettaskrecipe_attachments($taskid, $taskinstanceid, $filter_type = "")
	{
		$result = array();
		
		$path = tasks::gettaskrecipepath($taskid);
		$recipe = file_get_contents($path);
		
		// the attachments start after the first "~~~"
		$recipe_pieces = explode("~~~", $recipe);
		
		if (count($recipe_pieces) >= 2)
		{
			// skip the first part
			for ($index = 1; $index < count($recipe_pieces); $index++)
			{
				$recipe_piece = trim($recipe_pieces[$index]);
				$lines = explode("\n", $recipe_piece, 2);
				$firstline = $lines[0];
				// for example type=attachment id='nice'
				$attributes = shortcode_parse_atts($firstline);
				$id = $attributes["id"];
				$type = $attributes["type"];
				
				$shouldinclude = true;
				if ($shouldinclude && $filter_type != "" && $type != $filter_type)
				{
					$shouldinclude = false;
				}
				
				if ($shouldinclude && $id != "")
				{
					$result[] = $id;
				}
			}
		}
		
		return $result;
	}

	public static function gettaskrecipe_attachment($taskid, $taskinstanceid, $attachmentid)
	{
		$delegated_result = tasks::gettaskrecipe_attachment_raw($taskid, $attachmentid);
		$data = $delegated_result["data"];
		$type = $delegated_result["attributes"]["type"];
		if (false)
		{
		}
		else if ($type == "runtime_base64encoded_json_lookup")
		{
			if ($taskinstanceid != false && $taskinstanceid != "")
			{			
				// we exclude the specific attachmentid to avoid endless loops
				$lookup_args = array
				(
					"exclude_keys" => array($attachmentid)
				);
				$lookups = tasks::gettaskinstancelookup($taskid, $taskinstanceid, $lookup_args);
				// apply the lookups on the data before encoding it
				foreach ($lookups as $key => $val)
				{
					$data = str_replace("{{{$key}}}", "{$val}", $data);
				}
			}
			
			$value_array = json_decode($data, true);	// decode into array
			$result = json_encode($value_array, JSON_UNESCAPED_UNICODE);	// encode back to string so we have all wrapped in one line
			if ($result == null)
			{
				functions::throw_nack("tasks::gettaskrecipe_attachment; $taskid, $attachmentid (incorrect json?)");
			}
			$result = base64_encode($result);
		}
		else if ($type == "runtime_json_lookup")
		{
			$value_array = json_decode($data, true);	// decode into array
			$result = json_encode($value_array, JSON_UNESCAPED_UNICODE);	// encode back to string so we have all wrapped in one line
			if ($result == null)
			{
				functions::throw_nack("tasks::gettaskrecipe_attachment; $taskid, $attachmentid (incorrect json?)");
			}
			
			
		}
		else if ($type == "text")
		{
			$result = $data;		
		}
		else
		{
			$result = $data;
			$result = str_replace("\n", " ", $result);
			$result = str_replace("\t", " ", $result);
		}
		
		return $result;
	}

	public static function gettaskrecipe_attachment_raw($taskid, $attachmentid)
	{
		$path = tasks::gettaskrecipepath($taskid);
		$recipe = file_get_contents($path);
		
		// the attachments start after the first "~~~"
		$recipe_pieces = explode("~~~", $recipe);
		$found = false;
		
		if (count($recipe_pieces) >= 2)
		{
			// skip the first part
			for ($index = 1; $index <= count($recipe_pieces); $index++)
			{
				$recipe_piece = trim($recipe_pieces[$index]);
				$lines = explode("\n", $recipe_piece, 2);
				$firstline = $lines[0];
				// for example type=attachment id='nice'
				$attributes = shortcode_parse_atts($firstline);
				$result["attributes"] = $attributes;
				if ($attributes["id"] == $attachmentid)
				{
					$result["data"] = $lines[1];
					$found = true;
					break;
				}
			}
		}
		
		if (!$found)
		{
			functions::throw_nack("attachment not found?");
		}
		
		return $result;
	}

	public static function gettasks()
	{
		$getentitiesrawargs = array
		(
			"datasink_realm" => "tasks",
			"datasink_entitytype" => "task",
			"datasink_include_meta" => true
		);
		$entities = entity::getentitiesraw($getentitiesrawargs);
		$result = $entities;

		return $result;
	}

	public static function gettaskids()
	{
		$getentitiesrawargs = array
		(
			"datasink_realm" => "tasks",
			"datasink_entitytype" => "task",
			"datasink_include_meta" => false
		);
		$entities = entity::getentitiesraw($getentitiesrawargs);
		$result = array_keys($entities);

		return $result;
	}

	public static function gettaskinstanceids($taskid)
	{
		$getentitiesrawargs = array
		(
			"datasink_realm" => "tasks",
			"datasink_entitytype" => "task_{$taskid}_instances",
			"datasink_include_meta" => false
		);
		$entities = entity::getentitiesraw($getentitiesrawargs);
		$result = array_keys($entities);

		return $result;
	}

	public static function gettaskinstances($taskid)
	{
		$getentitiesrawargs = array
		(
			"datasink_realm" => "tasks",
			"datasink_entitytype" => "task_{$taskid}_instances",
			"datasink_include_meta" => true
		);
		$result = entity::getentitiesraw($getentitiesrawargs);
		return $result;
	}

	public static function gettaskinstancescount($taskid)
	{
		$getentitiesrawargs = array
		(
			"datasink_realm" => "tasks",
			"datasink_entitytype" => "task_{$taskid}_instances",
			"datasink_include_meta" => false
		);
		$entities = entity::getentitiesraw($getentitiesrawargs);
		$result = count($entities);
		return $result;
	}

	/*
	public static function batch_reverttorequiredstateparameters($taskid, $taskinstanceids)
	{
		if ($taskid == "") { functions::throw_nack("tasks_batch_reverttorequiredstateparameters; taskid not set"); }
		
		if (is_string($taskinstanceids))
		{
			$taskinstanceids = explode(";", $taskinstanceids);
		}
		
		$batchsize = count($taskinstanceids);
		$i = 1;
		error_log("tasks_batch_reverttorequiredstateparameters; $taskid; start(batchsize; $batchsize)");
		
		foreach ($taskinstanceids as $taskinstanceid)
		{
			error_log("tasks_batch_reverttorequiredstateparameters; executing $taskid; $taskinstanceid; ({$i}/{$batchsize}");
			tasks::reverttorequiredstateparameters($taskid, $taskinstanceid);
			
			// delegated_result is ignored; woud be -way- too much information, for debugging this can be enabled temporarily
			//$result[$taskinstanceid] = $delegated_result;
			
			$i++;
		}
		
		error_log("tasks_batch_reverttorequiredstateparameters; $taskid; end");
		
		$result = array();
		return $result;
	}
	*/

	/*
	public static function reverttorequiredstateparameters($taskid, $taskinstanceid)
	{
		$existingstateparameters = tasks::gettaskinstancestateparameters($taskid, $taskinstanceid);
		
		$meta = tasks::getreflectionmeta($taskid, $taskinstanceid);
		$required_fields = $meta["required_fields"];
		
		error_log("tasks::reverttorequiredstateparameters; $taskid, $taskinstanceid; required fields:" . json_encode($required_fields));
		
		$fields_to_be_removed = array();
		foreach ($existingstateparameters as $existingstateparameter => $existingstateparameterval)
		{
			if (!in_array($existingstateparameter, $required_fields))
			{
				$fields_to_be_removed[] = $existingstateparameter;
			}
		}
		
		error_log("tasks::reverttorequiredstateparameters; $taskid, $taskinstanceid; fields to be removed:" . json_encode($fields_to_be_removed));
		
		tasks::deletestateparameters_for_taskinstance($taskid, $taskinstanceid, $fields_to_be_removed);
		
		$result = array();
		return $result;
	}
	*/

	public static function setexecutionpointer($taskid, $taskinstanceid, $execution_pointer)
	{
		$meta = tasks::gettaskinstance($taskid, $taskinstanceid);
		
		$state = $meta["state"];
		if (false)
		{
		}
		else if ($state == "STARTED")
		{
			// ok
		}
		else
		{
			functions::throw_nack("tasks::setexecutionpointer; unsupported state; $state (for $taskid, $taskinstanceid)");
		}
		
		// verify the execution_pointer is valid
		$taskmeta = tasks::getreflectionmeta($taskid);
		if (!array_key_exists($execution_pointer, $taskmeta["task_instructions_by_id"]))
		{
			if ($execution_pointer == "")
			{
				// to reset, this is allowed
			}
			else 
			{
				functions::throw_nack("tasks::setexecutionpointer; invalid execution_pointer; $execution_pointer (for $taskid, $taskinstanceid)");
			}
		}
		
		$meta["execution_pointer"] = $execution_pointer;
		tasks::updatetaskinstance($taskid, $taskinstanceid, $meta);
		
		$result = array
		(
			"result" => "OK"
		);
		
		return $result;
	}

	public static function getexecutionpointer($taskid, $taskinstanceid)
	{
		$meta = tasks::gettaskinstance($taskid, $taskinstanceid);
		if (false)
		{
		}
		else if ($meta["state"] == "STARTED" || $meta["state"] == "CREATED")
		{
			$result = $meta["execution_pointer"];
			if ($result == null)
			{
				// return the first execution pointer according to the task
				$taskmeta = tasks::getreflectionmeta($taskid);
				$task_instructions = $taskmeta["task_instructions"];
				if (count($task_instructions) == 0)
				{
					functions::throw_nack("tasks::getexecutionpointer; no execution pointer set, and unable to return the first execution point as there are no task instructions for task $taskid");
				}
				
				$result = $task_instructions[0]["id"];
			}
		}
		
		return $result;
	}

	// //////

	public static function isexecutionpointerlegit($taskid, $executionpointer)
	{
		$instruction = tasks::getinstruction($taskid, $executionpointer);
		if ($instruction == false)
		{
			$result = false;
		}
		else
		{
			$result = true;
		}
		return $result;
	}

	public static function getinstruction($taskid, $executionpointer)
	{
		// returns the instruction pointer for the specified taskid
		$taskmeta = tasks::getreflectionmeta($taskid);
		
		/*
		{
		"id": "a974f8a3850ff6cc5564b9804ff6e342",
		"linenr": 9,
		"instructionnr": 3,
		"type": "set_parameter",
		"attributes": {
		"set_stateparameter_field": "designer",
		"function": "shortcode",
		"shortcode_type": "string",
		"ops": "modelproperty",
		"modeluri": "{{theme_itemmeta_id}}@nxs.divithemes.itemmeta",
		"property": "designer"
		}
	}
		*/
		$result = $taskmeta["task_instructions_by_id"][$executionpointer];
		if ($result == "")
		{
			$result = false;
		}
		
		return $result;
	}

	
	public static function getstacktracepreviousgeneration($taskid, $taskinstanceid)
	{
		$result = array();
		
		$args = array
		(
			"reverse" => true,
			"includecurrent" => true,
		);
		$stacktrace_current_generation = tasks::getstacktrace($taskid, $taskinstanceid, $args);
		foreach ($stacktrace_current_generation as $frame)
		{
			$taskid_current = $frame["taskid"];
			$taskinstanceid_current = $frame["taskinstanceid"];
			
			// 
			if ($taskid_current == 147)
			{
				$repliesto_taskid = $frame["stateparameters"]["repliesto_taskid"];
				$repliesto_taskinstanceid = $frame["stateparameters"]["repliesto_taskinstanceid"];
				
				// 
				
				$args = array
				(
					"reverse" => false,
					"includecurrent" => true,
				);
				$result = tasks::getstacktrace($repliesto_taskid, $repliesto_taskinstanceid, $args);
				break;
			}
		}
		
		return $result;
	}

	public static function getstacktrace($taskid, $taskinstanceid, $args)
	{
		$maxdepth = $args["maxdepth"];
		if (!isset($maxdepth)) { $maxdepth = 100; }

		$includecurrent = $args["includecurrent"];
		// unset includecurrent in args now, as for the childs we should not include the current
		unset($args["includecurrent"]);
		
		$result = array();
		tasks::getstacktrace_internal($taskid, $taskinstanceid, $maxdepth, $result);
		
		if ($includecurrent)
		{
			$result[] = tasks::gettaskinstance($taskid, $taskinstanceid);
		}
		
		$reverse = $args["reverse"];
		if ($reverse == true)
		{
			$result = array_reverse($result);
		}
		
		return $result;
	}

	public static function getstacktrace_internal($taskid, $taskinstanceid, $maxdepth = 100, &$resultsofar = array())
	{
		if ($maxdepth <= 0)
		{
			functions::throw_nack("reached max depth? (1); $taskid, $taskinstanceid; $maxdepth; resultsofar; " . json_encode($resultsofar));
		}
		
		$taskmeta = tasks::gettaskinstance($taskid, $taskinstanceid);	
		$parent_taskid = $taskmeta["createdby_taskid"];
		$parent_taskinstanceid = $taskmeta["createdby_taskinstanceid"];
		
		// downwards compatible code; in the old versions (prior to 1 aug 2019) the
		// createdby_taskid and createdby_taskinstanceid were stored in stateparameters
		if ($parent_taskid == "" || $parent_taskinstanceid == "")
		{
			$stateparameters_current = tasks::gettaskinstancestateparameters($taskid, $taskinstanceid);
			$parent_taskid = $stateparameters_current["createdby_taskid"];
			$parent_taskinstanceid = $stateparameters_current["createdby_taskinstanceid"];
		}
		
		if ($parent_taskid != "" && $parent_taskinstanceid != "")
		{
			tasks::getstacktrace_internal($parent_taskid, $parent_taskinstanceid, $maxdepth - 1, $resultsofar);
			$resultsofar[] = tasks::gettaskinstance($parent_taskid, $parent_taskinstanceid);
		}
	}

	public static function gettaskinstancemetawithrecursiveoffspring($taskid, $taskinstanceid, $maxdepth)
	{
		$result = array();
		$this_meta = tasks::gettaskinstance($taskid, $taskinstanceid);
		$result[$taskid][$taskinstanceid] = $this_meta;
		
		$subresults = tasks::gettaskinstancemetarecursiveoffspring_internal($taskid, $taskinstanceid, $maxdepth);
		foreach ($subresults as $subresult)
		{
			$subtaskid = $subresult["taskid"];
			$subtaskinstanceid = $subresult["taskinstanceid"];
			
			$result[$subtaskid][$subtaskinstanceid] = $subresult;
		}
		
		return $result;
	}

	public static function gettaskinstancemetarecursiveoffspring_internal($taskid, $taskinstanceid, $maxdepth = 2)
	{
		$result = array();
		
		if ($maxdepth <= 0)
		{
			functions::throw_nack("reached max depth? (2)");
		}
		
		$this_meta = tasks::gettaskinstance($taskid, $taskinstanceid);
		$children_of_this = $this_meta["created_tasks"];
		foreach ($children_of_this as $child_of_this)
		{
			$child_taskid = $child_of_this["taskid"];
			$child_taskinstanceid = $child_of_this["taskinstanceid"];
			$child_meta = tasks::gettaskinstance($child_taskid, $child_taskinstanceid);
			
			$result[] = $child_meta;
			
			$grandchildren_of_this = $child_meta["created_tasks"];
			if (count($grandchildren_of_this) > 0)
			{
				if ($maxdepth > 0)
				{
					// recursive call
					$newmaxdepth = $maxdepth - 1;
					$subresult = tasks::gettaskinstancemetarecursiveoffspring_internal($child_taskid, $child_taskinstanceid, $newmaxdepth);
					$result = array_merge($result, $subresult);
				}
				else
				{
					// avoid getting too deep (and endless recursions)
				}
			}
		}
		
		return $result;
	}

	public static function deep_searchtaskinstances($taskid, $fields_filter, $limit, $state)
	{
		$result["taskinstances"] = array();
		
		// $fields_filter='aap=1 noot=2'
		$criteria = array();
		if ($fields_filter != "")
		{
			$fields_filter_keyvaluecombi_pieces = explode(" ", $fields_filter);	// 'aap=1' 'noot=2'
			foreach ($fields_filter_keyvaluecombi_pieces as $fields_filter_keyvaluecombi_piece)		// 'aap=1'
			{
				$keyvaluepieces = explode("=", $fields_filter_keyvaluecombi_piece, 2);	// 'aap' '1'
				$criteria[$keyvaluepieces[0]] = $keyvaluepieces[1];
			}
		}

		$break_outer_loop = false;
		
		$matches_so_far = 0;

		// find all tasks
		// foreach task
		//   find all instances
		//   for each instance
		//     if (filter applies)
		//       include
		
		$getentitiesrawargs = array
		(
			"datasink_realm" => "tasks",
			"datasink_entitytype" => "task",
			"datasink_include_meta" => false
		);
		$tasks = entity::getentitiesraw($getentitiesrawargs);

		foreach ($tasks as $taskid => $ignore)
		{
			$gettaskinstanceargs = array
			(
				"datasink_realm" => "tasks",
				"datasink_entitytype" => "task_{$taskid}_instances",
				"datasink_include_meta" => true
			);
			$taskinstances = entity::getentitiesraw($gettaskinstanceargs);
	
			foreach($taskinstances as $taskinstance)
			{
				$shouldinclude = true;
				if ($shouldinclude)
				{
					$result[] = $taskinstance;
				}	
			}
		}

		return $result;
	}

	public static function search_evaluate_if_this($if_this, $taskid, $taskmeta)
	{
		if ($if_this == null)
		{
			functions::throw_nack("if_this is not set");
		}
		
		$result = array();
		
		$if_this_type = $if_this["type"];
		if (false)
		{
		}
		else if ($if_this_type == "true_if_not_current")
		{
			$should_not_be_taskid = $_REQUEST["taskid"];
			if ($should_not_be_taskid == "")
			{
				functions::throw_nack("expected taskid query parameter to be set"); 
			}
			$should_not_be_taskinstanceid = $_REQUEST["taskinstanceid"];
			if ($should_not_be_taskinstanceid == "")
			{
				functions::throw_nack("expected taskinstanceid query parameter to be set"); 
			}
			if (tasks::isheadless())
			{
				functions::throw_nack("true_if_not_current can only be used in gui mode (you are headless)"); 
			}
			$taskinstanceid = $taskmeta["taskinstanceid"];
			
			if ($taskid == $should_not_be_taskid && $taskinstanceid == $should_not_be_taskinstanceid)
			{
				$result["conclusion"] = false;
			}
			else
			{
				$result["conclusion"] = true;
			}
		}
		else if ($if_this_type == "true_if_in_any_of_the_required_states")
		{
			$any_of_the_required_states = $if_this["any_of_the_required_states"];
			$value = $taskmeta["state"];
			if (in_array($value, $any_of_the_required_states))
			{
				$result["conclusion"] = true;
			}
			else
			{
				$result["conclusion"] = false;
			}
		}
		else if ($if_this_type == "true_if_stateparameter_has_required_value_for_key")
		{
			$key = $if_this["key"];
			$required_value = $if_this["required_value"];
			$value = $taskmeta["stateparameters"][$key];
			if ($required_value == $value)
			{
				$result["conclusion"] = true;
			}
			else
			{
				$result["conclusion"] = false;
			}
		}
		else if ($if_this_type == "true_if_stateparameter_not_has_required_value_for_key")
		{
			$key = $if_this["key"];
			$required_value = $if_this["value"];
			$value = $taskmeta["stateparameters"][$key];
			if ($required_value != $value)
			{
				$result["conclusion"] = true;
			}
			else
			{
				$result["conclusion"] = false;
			}
		}
		else if ($if_this_type == "true_if_task_has_required_taskid")
		{
			$required_taskid = $if_this["required_taskid"];
			if (functions::stringcontains($required_taskid, ";"))
			{
				$required_taskids = explode(";", $required_taskid);
			}
			else
			{
				$required_taskids = array($required_taskid);
			}
			
			if (in_array($taskid, $required_taskids))
			{
				$result["conclusion"] = true;
			}
			else
			{
				$result["conclusion"] = false;
			}
		}
		else if ($if_this_type == "true_if_ended_within_number_of_hours_ago")
		{
			$within_number_of_hours_ago = $if_this["within_number_of_hours_ago"];
			$endtime = $taskmeta["endtime"];
			if ($endtime != "")
			{
				$now = time();
				$required_ended_after = $now - ($within_number_of_hours_ago * 3600);
				if ($endtime > $required_ended_after)
				{
					$result["conclusion"] = true;
				}
				else
				{
					$result["conclusion"] = false;
				}
			}
			else
			{
				$result["conclusion"] = false;
			}
		}
		else if ($if_this_type == "true_if_aborted_within_number_of_hours_ago")
		{
			$within_number_of_hours_ago = $if_this["within_number_of_hours_ago"];
			$abortedtime = $taskmeta["abortedtime"];
			
			if ($abortedtime != "")
			{
				$now = time();
				$required_aborted_after = $now - ($within_number_of_hours_ago * 3600);
				
				if ($abortedtime > $required_aborted_after)
				{
					$result["conclusion"] = true;
				}
				else
				{
					$result["conclusion"] = false;
				}
			}
			else
			{
				$result["conclusion"] = false;
			}
		}
		else if ($if_this_type == "true_if_assigned_to_any_of_the_required_employees")
		{
			$any_of_the_required_employees = $if_this["any_of_the_required_employees"];
			$value = $taskmeta["assignedtouserid"];
			if (in_array($value, $any_of_the_required_employees))
			{
				$result["conclusion"] = true;
			}
			else
			{
				$result["conclusion"] = false;
			}
		}
		else if ($if_this_type == "true_if_taskinstance_notyetassignedtoemployee")
		{
			$assignedtouserid = $taskmeta["assignedtouserid"];
			
			if ($assignedtouserid == "")
			{
				$result["conclusion"] = true;
			}
			else
			{
				$result["conclusion"] = false;
			}
		}
		else if ($if_this_type == "true_if_each_subcondition_is_true")
		{
			$result["conclusion"] = true;
			$subconditions = $if_this["subconditions"];
			foreach ($subconditions as $subcondition)
			{
				if ($subcondition == null) { functions::throw_nack("subcondition is not set in subconditions of: " . json_encode($if_this)); }
				$evaluate_subconditions = tasks::search_evaluate_if_this($subcondition, $taskid, $taskmeta);
				if ($evaluate_subconditions["conclusion"] == false)
				{
					$result["conclusion"] = false;
					break;
				}
			}
		}
		else if ($if_this_type == "true_if_at_least_one_subcondition_is_true")
		{
			$result["conclusion"] = false;
			$subconditions = $if_this["subconditions"];
			foreach ($subconditions as $subcondition)
			{
				if ($subcondition == null) { functions::throw_nack("subcondition is not set in subconditions of: " . json_encode($if_this)); }
				$evaluate_subconditions = tasks::search_evaluate_if_this($subcondition, $taskid, $taskmeta);
				if ($evaluate_subconditions["conclusion"] == true)
				{
					$result["conclusion"] = true;
					break;
				}
			}
		}
		else if ($if_this_type == "")
		{
			functions::throw_nack("type not specified in " . json_encode($if_this) . "");
		}
		else
		{
			functions::throw_nack("unsupported type (1); '$if_this_type' in " . json_encode($if_this) . "");
		}
		
		return $result;
	}

	/*
	public static function archive_taskinstances($taskid)
	{
		$result = array();
		
		if ($taskid == "")
		{
			functions::throw_nack("taskid not set");
		}
		
		$secsinday = 86400;

		$daystokeep = do_shortcode("[string ops=modelproperty modeluri='{$taskid}@nxs.p001.businessprocess.task' property='daystokeep_before_archiving']");
		error_log("archiving; daystokeep: $daystokeep");
		if ($daystokeep == "")
		{
			$daystokeep = 30;
		}
		$rightnow = time();
		$archiveclosedinstancesbeforetime = $rightnow - ($daystokeep * $secsinday);
		
		$skipped = array();
		
		$instances = tasks::getinstances($taskid);
		foreach ($instances as $taskinstanceid => $instance)
		{
			$state = $instance["state"];
			$createtime = $instance["createtime"];
			
			if ($createtime == "")
			{
				$skipped[] = array
				(
					"taskid" => $taskid,
					"taskinstanceid" => $taskinstanceid,
				);
				continue;
			}
			
			
			if ($createtime == "")
			{
				functions::throw_nack("createtime is not set for taskid:$taskid taskinstanceid $taskinstanceid; " . json_encode($instance));
			}
			
			if (false)
			{
				//
			}
			else if ($state == "ENDED")
			{
				$endtime = $instance["endtime"];
				if ($endtime < $archiveclosedinstancesbeforetime)
				{
					$toarchive[] = $instance;
				}
			}
			else if ($state == "ABORTED")
			{
				$endtime = $instance["abortedtime"];
				if ($endtime < $archiveclosedinstancesbeforetime)
				{
					$toarchive[] = $instance;
				}
			}
			else if ($state == "CREATED")
			{
				// will NOT be archived; its still active
			}
			else if ($state == "STARTED")
			{
				// will NOT be archived; its still active
			}
			else
			{
				echo "unsupported state; $state";
				var_dump($instance);
				die();
				
				// $toarchive[] = $instance;
			}
		}
		
		$count = count($toarchive);
		if ($count > 0)
		{
			$result["count_processed_archived_items"] = $count;
			
			// step 2; append all instances to the archives
			$r = tasks::appenditemstoarchive($taskid, $toarchive);
			$result["actions"]["tasks::appenditemstoarchive"] = $r;
			
			// step 3; delete all appended instances from the "active" file
			$r = tasks::purgeinstances($taskid, $toarchive);
			$result["actions"]["tasks::purgeinstances"] = $r;
		}
		else
		{
			$result["count_processed_archived_items"] = $count;
		}
		
		$result["skipped"] = $skipped;
		
		return $result;
	}

	public static function createtaskinstance_byinvokingapi($taskidofinstancetocreate, $stateparameters, $createdby_taskid, $createdby_taskinstanceid)
	{
		$action_url = "https://global.nexusthemes.com/api/1/prod/create-task-instance/";
		$postargs = array();
		foreach ($stateparameters as $key => $val)
		{
			$postargs[$key] = $val;
		}
		$postargs["nxs"] = "businessprocess-api";
		$postargs["json_output_format"] = "prettyprint";
		$postargs["businessprocesstaskid"] = $taskidofinstancetocreate;
		$postargs["createdby_taskid"] = $createdby_taskid;
		$postargs["createdby_taskinstanceid"] = $createdby_taskinstanceid;

		//
		$args["url"] = $action_url;
		$args["method"] = "POST";	
		$args["postargs"] = $postargs;
		$action_string = functions::geturlcontents($args);
		
		$action_result = json_decode($action_string, true);
		if ($action_result["result"] != "OK") 
		{
			$last_error = error_get_last();
			$result = array
			(
				"result" => "NACK",
				"details" => "unable to fetch action_url (tasks); $action_url",
				"lasterror" => json_encode($last_error),
			);
		}
		else
		{
			$result["result"] = "OK";
			$result["action_result"] = $action_result;
		}
		
		return $result;
	}
	*/

	// free key
	public static function getunallocatedstateparameter($base_of_key, $stateparameters, $maxindex = 99)
	{
		$result = false;
		$index = 0;
		while ($index < $maxindex)
		{
			$possible_key = "{$base_of_key}_i{$index}";
			if (!isset($stateparameters[$possible_key]))
			{
				$result = $possible_key;
				break;
			}
			
			$index++;
		}
		
		if ($result == false)
		{
			functions::throw_nack("unable to find unallocated input parameter $base_of_key $maxindex");
		}
		
		return $result;
	}

	public static function get_ordered_task_instances_requiring_batch_processing()
	{
		// todo; optimize this function; it should only return the next upcoming
		// instance which requires batch processing (or empty if its not existing)
		global $g_modelmanager;

		// get all tasks
		
		$schema = "nxs.p001.businessprocess.task";
		global $g_modelmanager;
		$a = array
		(
			"singularschema" => $schema,
		);
		$allentries = $g_modelmanager->gettaxonomypropertiesofallmodels($a);
		
		$items_requiring_batch_processing = array();
		
		foreach ($allentries as $entry)
		{
			$taskid = $entry["id"];
			$handle_prio = $entry["handle_prio"];
			$processing_type = $entry["processing_type"];
			if ($processing_type == "automated")
			{
				$instances = tasks::gettaskinstances($taskid);
				foreach ($instances as $instanceid => $instancemeta)
				{
					$state = $instancemeta["state"];
					if ($state == "CREATED")
					{
						$items_requiring_batch_processing[] = array
						(
							"handle_prio" => $handle_prio,
							"taskid" => $taskid,
							"taskinstanceid" => $instanceid,
						);
					}
				}
			}
		}
		
		usort($items_requiring_batch_processing, function ($a, $b) 
		{
			return $b['handle_prio'] <=> $a['handle_prio'];
		});
		
		return $items_requiring_batch_processing;
	}

	public static function starttaskinstance($taskid, $taskinstanceid, $assigntouserid)
	{
		if ($taskid == "") { functions::throw_nack("businessprocesstaskid not set"); }
		if ($taskinstanceid == "") { functions::throw_nack("taskinstanceid not set"); }

		$taskinstance = tasks::gettaskinstance($taskid, $taskinstanceid);
		$oldstate = $taskinstance["state"];
		if ($oldstate != "CREATED") { functions::throw_nack("unexpected old state; $oldstate (expected CREATED)"); }
		
		$taskinstance["state"] = "STARTED";
		$taskinstance["starttime"] = time();
		
		if ($assigntouserid != "")
		{
			$taskinstance["assignedtouserid"] = $assigntouserid;
		}

		$duration_secs = $taskinstance["starttime"] - $taskinstance["createtime"];
		$duration_human = functions::getsecondstohumanreadable($duration_secs);

		$updateresult = tasks::updatetaskinstance($taskid, $taskinstanceid, $taskinstance);

		$result = array
		(
			"updateresult" => $updateresult,
			"duration_secs" => $duration_secs,
			"duration_human" => $duration_human,
		);
		
		return $result;
	}

	public static function endtaskinstance($taskid, $taskinstanceid)
	{
		if ($taskid == "") { functions::throw_nack("businessprocesstaskid not set"); }
		if ($taskinstanceid == "") { functions::throw_nack("taskinstanceid not set"); }
		
		$instancemeta = tasks::gettaskinstance($taskid, $taskinstanceid);
		if ($instancemeta["isfound"] == false) { functions::throw_nack("instance not found?"); }
		
		$oldstate = $instancemeta["state"];
		if ($oldstate == "ENDED") { return; }
		if ($oldstate != "STARTED") { functions::throw_nack("unexpected old state; $oldstate"); }
		
		// consider waking up parents that sleep
		// tasks::handle_event_before_closingtaskinstance($taskid, $taskinstanceid);
		
		$instancemeta["state"] = "ENDED";
		$instancemeta["endtime"] = time();
		$instancemeta["endedbyip"] = $_SERVER['REMOTE_ADDR'];
		$instancemeta["execution_pointer"] = ".";
		//
		
		tasks::updatetaskinstance($taskid, $taskinstanceid, $instancemeta);

		$duration_secs = $instancemeta["endtime"] - $instancemeta["starttime"];
		$duration_human = functions::getsecondstohumanreadable($duration_secs);
		
		$result = array
		(
			//"path" => $path,
			//"length" => strlen($string),
			"duration_secs" => $duration_secs,
			"duration_human" => $duration_human,
		);
		
		return $result;
	}

	public static function batch_resetnonerequiredstateparameters($taskid, $taskinstanceids)
	{
		if ($taskid == "") { functions::throw_nack("tasks_batch_resetnonerequiredstateparameters; taskid not set"); }
		
		if (is_string($taskinstanceids))
		{
			$taskinstanceids = explode(";", $taskinstanceids);
		}
		
		$batchsize = count($taskinstanceids);
		$i = 1;
		error_log("tasks_batch_resetnonerequiredstateparameters; $taskid; start(batchsize; $batchsize)");
		
		foreach ($taskinstanceids as $taskinstanceid)
		{
			error_log("tasks_batch_resetnonerequiredstateparameters; executing $taskid; $taskinstanceid; ({$i}/{$batchsize}");
			tasks::batch_resetnonerequiredstateparameters($taskid, $taskinstanceid);
			
			// delegated_result is ignored; woud be -way- too much information, for debugging this can be enabled temporarily
			//$result[$taskinstanceid] = $delegated_result;
			
			$i++;
		}
		
		error_log("tasks_batch_resetexecutionpointers; $taskid; end");
		
		$result = array();
		return $result;
	}

	public static function batch_resetexecutionpointers($taskid, $taskinstanceids)
	{
		if ($taskid == "") { functions::throw_nack("tasks_batch_resetexecutionpointers; taskid not set"); }
		
		if (is_string($taskinstanceids))
		{
			$taskinstanceids = explode(";", $taskinstanceids);
		}
		
		$batchsize = count($taskinstanceids);
		$i = 1;
		error_log("tasks_batch_resetexecutionpointers; $taskid; start(batchsize; $batchsize)");
		
		$executionmode = "ALL_REMAINING_TASK_INSTRUCTIONS";
		foreach ($taskinstanceids as $taskinstanceid)
		{
			error_log("tasks_batch_resetexecutionpointers; executing $taskid; $taskinstanceid; ({$i}/{$batchsize}");
			$execution_pointer = "";
			tasks::setexecutionpointer($taskid, $taskinstanceid, $execution_pointer);
			
			// delegated_result is ignored; woud be -way- too much information, for debugging this can be enabled temporarily
			//$result[$taskinstanceid] = $delegated_result;
			
			$i++;
		}
		
		error_log("tasks_batch_resetexecutionpointers; $taskid; end");
		
		$result = array();
		return $result;
	}

	public static function execute_batch_headless_from_current_execution_pointer($taskid, $taskinstanceids)
	{
		if ($taskid == "") { functions::throw_nack("tasks_execute_batch_headless_from_current_execution_pointer; taskid not set"); }
		
		if (is_string($taskinstanceids))
		{
			$taskinstanceids = explode(";", $taskinstanceids);
		}
		
		$batchsize = count($taskinstanceids);
		$i = 1;
		error_log("tasks_execute_batch_headless_from_current_execution_pointer; $taskid; start(batchsize; $batchsize)");
		
		$executionmode = "ALL_REMAINING_TASK_INSTRUCTIONS";
		foreach ($taskinstanceids as $taskinstanceid)
		{
			error_log("tasks_execute_batch_headless_from_current_execution_pointer; executing $taskid; $taskinstanceid; ({$i}/{$batchsize}");
			$delegated_result = tasks::execute_headless_from_current_execution_pointer($taskid, $taskinstanceid, $executionmode);
			
			// delegated_result is ignored; woud be -way- too much information, for debugging this can be enabled temporarily
			//$result[$taskinstanceid] = $delegated_result;
			
			$i++;
		}
		
		error_log("tasks_execute_batch_headless_from_current_execution_pointer; $taskid; end");
		
		$result = array();
		return $result;
	}

	public static function execute_headless_from_current_execution_pointer($taskid, $taskinstanceid, $executionmode)
	{
		// checks
		if ($taskid == "") { functions::throw_nack("tasks::execute_headless_from_current_execution_pointer; taskid not set"); }
		if ($taskinstanceid == "") { functions::throw_nack("tasks::execute_headless_from_current_execution_pointer; taskinstanceid not set"); }
		$executionmodes = array("CURRENT_TASK_INSTRUCTION_ONLY", "ALL_REMAINING_TASK_INSTRUCTIONS");
		if (!in_array($executionmode, $executionmodes)) { functions::throw_nack("tasks::execute_headless_from_current_execution_pointer; invalid executionmode value; $executionmode valid modes are:" . json_encode($executionmodes)); }
		
		//
		$throw_error_when_loop_detected = true;	// we could also allow this, which would allow 'goto statements'
		$processed_execution_pointers_in_this_invocation = array();
		$executions_left = 1500;	// this is to prevent endless loops, increase accordingly
		
		while ($executions_left > 0)
		{
			$executions_left--;
			
			$meta = tasks::gettaskinstance($taskid, $taskinstanceid);
			
			$state = $meta["state"];
			if (false)
			{
			}
			else if ($state == "CREATED")
			{
				// ok
			}
			else if ($state == "STARTED")
			{
				// ok
			}
			else
			{
				functions::throw_nack("tasks::execute_headless_from_current_execution_pointer; unsupported state; $state (for $taskid, $taskinstanceid)");
			}
			
			// update state to 'STARTED' here if its CREATED
			if ($state == "CREATED")
			{
				tasks::starttaskinstance($taskid, $taskinstanceid, "");
				
				$state = "STARTED";
			}
			
			$execution_pointer = tasks::getexecutionpointer($taskid, $taskinstanceid);
			
			//error_log("tasks::execute_headless_from_current_execution_pointer; about to process execution of instruction at pointer $execution_pointer for; $taskid; $taskinstanceid");
			
			if ($throw_error_when_loop_detected)
			{
				if (in_array($execution_pointer, $processed_execution_pointers_in_this_invocation))
				{
					functions::throw_nack("tasks::setexecutionpointer; already processed $execution_pointer in this invocation?");
				}
			}
			$processed_execution_pointers_in_this_invocation[] = $execution_pointer;
			
			// verify the execution_pointer is valid
			$taskmeta = tasks::getreflectionmeta($taskid);
			if (!array_key_exists($execution_pointer, $taskmeta["task_instructions_by_id"]))
			{
				functions::throw_nack("tasks::setexecutionpointer; invalid execution_pointer; $execution_pointer (for $taskid, $taskinstanceid); either reset the execution pointer or specify a proper one");
			}
			
			$taskinstruction = $taskmeta["task_instructions_by_id"][$execution_pointer];
			$attributes = $taskinstruction["attributes"];
			
			// execute the instruction on the existing execution_pointer
			if (true)
			{
				$type = $taskinstruction["type"];

				tasks::ensuretaskinstructionloaded($type);

				$class = "barkgj\tasks\taskinstruction\{$type}";
				$instance = new $class();
				$execution_result = $instance->execute($taskid, $taskinstanceid, $attributes);
				
				// extend the stacktrace to we know what we did in case something goes wrong or bad
				$result["processed_items"][] = array
				(
					"attributes" => $attributes,
					"do_result" => $execution_result,
				);
				
				// replicate the console output to the invoker
				foreach ($execution_result["console"] as $line)
				{
					$result["console"][] = $line;
				}
				
				// don't invoke other workflows if this one produced a NACK
				// in this case the execution pointer will not be shifted
				if ($execution_result["result"] != "OK")
				{
					$result["result"] = "NACK";
					$result["nack_details"]["function"]["name"] = $type;
					$result["nack_details"]["function"]["args"] = $attributes;
					
					return $result;
				}
			}
			
			// if we reach this point, it means the taskinstruction was executed properly
			// update the execution pointer
			$oldinstructionnr = $taskinstruction["instructionnr"];
			
			// error_log("oldinstructionnr:$oldinstructionnr");
			
			$newinstructionnr = $oldinstructionnr+1;
			//
			$newinstruction = $taskmeta["task_instructions"][$newinstructionnr];
			
			if (!isset($newinstruction))
			{
				// no more instructions left, we are done!
				tasks::endtaskinstance($taskid, $taskinstanceid);
				
				// break the loop
				break;
			}
			
			$new_execution_pointer = $newinstruction["id"];
			tasks::setexecutionpointer($taskid, $taskinstanceid, $new_execution_pointer);
			
			
			// depending on the specified execution mode, either proceed with the next instruction (=loop),
			// or return the result back to the invoker
			if (false)
			{
				//
			}
			else if ($executionmode == "CURRENT_TASK_INSTRUCTION_ONLY")
			{
				// don't execute the next task instruction, only one instructions per invocation was requested
				break;
			}
			else if ($executionmode == "ALL_REMAINING_TASK_INSTRUCTIONS")
			{
				// proceed with the next one, if its there
			}
			else
			{
				functions::throw_nack("tasks::execute_headless_from_current_execution_pointer; unsupported executionmode; $executionmode");
			}
			
			// we will loop here (unless we we run out of exection attempts)
			// error_log("tasks::execute_headless_from_current_execution_pointer; $taskid; $taskinstanceid; next instruction coming up to be executed ($new_execution_pointer)...");
		}
		
		error_log("tasks::execute_headless_from_current_execution_pointer; finished for; $taskid; $taskinstanceid");
		
		return $result;
	}

	/*
	public static function ui_getrendered_taskinstances($taskinstances, $renderargs)
	{
		$entries = array();
		foreach ($taskinstances as $taskinstance)
		{
			$taskid = $taskinstance["taskid"];
			$title = tasks::gettasktitle($taskid);
			$taskinstanceid = $taskinstance["taskinstanceid"];
			$instancemeta = tasks::gettaskinstance($taskid, $taskinstanceid);
			
			$entry["taskid"] = $taskid;
			$entry["tasktitle"] = $title;
			$entry["taskinstanceid"] = $taskinstanceid;
			$entry = array_merge($entry, $instancemeta);
			
			$entries[] = $entry;
		}
		
		$orderby = $renderargs["orderby"];
		if ($orderby == "")
		{
			$orderby = "createtime";
		}
		
		// order by the given column
		usort($entries, function ($item1, $item2) use ($orderby)
		{
			return $item1[$orderby] <=> $item2[$orderby];
		});

		$html = "";
		$html .= "<table class='rendertaskinstancesv2'>";
		$html .= "<tr>";
		$html .= "<td>Task id</td>";
		$html .= "<td>Title</td>";
		$html .= "<td>State</td>";
		$html .= "<td>Create time</td>";
		$html .= "<td>ID</td>";
		$html .= "<td>Input parameters</td>";
		$html .= "</tr>";
		foreach ($entries as $entry)
		{
			$createtime = $entry["createtime"];
			$createtime_html = "";
			$createtime_html .= date("Ymd H:i:s", $createtime);
			
			$state = $entry["state"];
			if ($state == "ABORTED")
			{
				$abort_reason = $entry["abort_reason"];
				$abort_note = $entry["abort_note"];
				
				$state_html = $state . "<br />{$abort_reason}<br />{$abort_note}";
			}
			else
			{
				$state_html = $state;
			}
			
			$taskid = $entry["taskid"];
			$title = $entry["tasktitle"];
			$taskinstanceid = $entry["taskinstanceid"];
			
			$action_url = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={$taskid}&taskinstanceid={$taskinstanceid}";
			
			// output settings; determines which stateparameters to output
			$o_stateparameters = $renderargs["o_stateparameters"];
			$o_stateparameters_list = explode(";", $o_stateparameters);
			
			$stateparameters = $entry["stateparameters"];
			$parameters_html = "";
			foreach ($stateparameters as $stateparameter => $val)
			{
				$shouldshow = true;
				if ($o_stateparameters != "")
				{
					if (!in_array($stateparameter, $o_stateparameters_list))
					{
						$shouldshow = false;
					}
				}
				
				if ($shouldshow)
				{
					
					$parameters_html .= "{$stateparameter} : {$val}<br />";
				}
			}
			
			$html .= "<tr class='state-{$state}'>";
			$html .= "<td>{$taskid}</td>";
			$html .= "<td>{$title}</td>";
			$html .= "<td>{$state_html}</td>";
			$html .= "<td>{$createtime_html}</td>";
			$html .= "<td><a target='_blank' href='{$action_url}'>{$taskinstanceid}</a></td>";
			$html .= "<td>$parameters_html</td>";
			$html .= "</tr>";
		}
		$html .= "</table>";
		
		
		$more = <<<EOD
		<style>
		.rendertaskinstancesv2{
			width:100%; 
			border-collapse:collapse; 
		}
		.rendertaskinstancesv2 td{ 
			padding:7px; border:#4e95f4 1px solid;
		}
		.rendertaskinstancesv2 tr{
			background: #b8d1f3;
		}
		.rendertaskinstancesv2 tr:nth-child(odd){ 
			background: #b8d1f3;
		}
		.rendertaskinstancesv2 tr:nth-child(even){
			background: #dae5f4;
		}
		.rendertaskinstancesv2 tr
		{
			opacity: 0.5;
		}
		.rendertaskinstancesv2 tr.state-CREATED,
		.rendertaskinstancesv2 tr.state-STARTED
		{
			opacity: 1.0;
		}
		</style>
		EOD;
		$more = str_replace("\n", "", $more);
		$more = str_replace("\r", "", $more);
		$html .= $more;
		
		return $html;
	}
	*/

	/*
	public static function getarchivescontainerpath()
	{
		$basefolder = tasks::getbasefolder();
		$result = "{$basefolder}/archives/";
		echo "tasks::getarchivescontainerpath:{$result}";
		die();
		return $result;
	}
	*/

	
	/*
	public static function willtriggerwakeparentwhenclosingtaskinstance($taskid, $taskinstanceid)
	{
		$result = false;
		
		//
		// if this instance has a parent consider waking it up
		//
		$instancemeta = tasks::gettaskinstance($taskid, $taskinstanceid);
		
		$parent_taskid = $instancemeta["createdby_taskid"];
		$parent_taskinstanceid = $instancemeta["createdby_taskinstanceid"];
		if ($parent_taskid != "" || $parent_taskinstanceid != "")
		{
			// if the parent instance is sleeping, consider waking it up
			$parentmeta = tasks::gettaskinstance($parent_taskid, $parent_taskinstanceid);
			$parent_state = $parentmeta["state"];
			
			if ($parent_state == "SLEEPING")
			{
				// awake the sleeping parent if it has no other children that are open (except for us)
				$count_open_children_of_parent_other_than_this = 0;
				
				$children_of_parent_task = $parentmeta["created_tasks"];
				foreach ($children_of_parent_task as $child_of_parent_task)
				{
					$offspring_of_parent_taskid = $child_of_parent_task["taskid"];
					$offspring_of_parent_taskinstanceid = $child_of_parent_task["taskinstanceid"];
					
					if ($offspring_of_parent_taskid == $taskid && $offspring_of_parent_taskinstanceid == $taskinstanceid)
					{
						// its "us", ignore
						continue;
					}
					else
					{
						$offspring_of_parent_state = tasks::getinstancestate($offspring_of_parent_taskid, $offspring_of_parent_taskinstanceid);
						
						//
						if (false)
						{
						}
						else if ($offspring_of_parent_state == "CREATED")
						{
							$count_open_children_of_parent_other_than_this++;
						}
						else if ($offspring_of_parent_state == "STARTED")
						{
							$count_open_children_of_parent_other_than_this++;
						}
						else if ($offspring_of_parent_state == "SLEEPING")
						{
							$count_open_children_of_parent_other_than_this++;
						}
						else if ($offspring_of_parent_state == "ENDED")
						{
							// means not open
						}
						else if ($offspring_of_parent_state == "ABORTED")
						{
							// means not open
						}
						else
						{
							functions::throw_nack("unsupported offspring_of_parent_state; $offspring_of_parent_state");
						}
					}
				}
				
				if ($count_open_children_of_parent_other_than_this == 0)
				{
					// this means the current task is the last one being closed,
					// wake the parent!
					// we wake the parent first before closing the current one,
					// this is to avoid the case if closing the current one fails,
					// the parent would otherwise not be closed
					$should_wakeup_parent = true;
				}
				
				if ($should_wakeup_parent)
				{
					$result = true;
				}
				else
				{
					//error_log("no need to wake up parent (other pending child task instances prevent this)");
				}
			}
			else
			{
				//error_log("no need to wake up parent (parent wasnt sleeping)");
			}
		}
		else
		{
			// 
			//error_log("no need to wake up parent; there is no parent task instance");
		}
		
		return $result;
	}
	*/

	/*
	// handle event happening just before closing a task instance
	public static function handle_event_before_closingtaskinstance($taskid, $taskinstanceid)
	{
		if (true)
		{
			if (tasks::willtriggerwakeparentwhenclosingtaskinstance($taskid, $taskinstanceid))
			{
				tasks::wakeparentoftaskinstance($taskid, $taskinstanceid);
			}
		}
	}

	public static function purgeinstances($taskid, $itemstopurge)
	{
		$result = array();
		
		$instances = tasks::gettaskinstances($taskid);
		$result["countbefore"] = count($instances);
		
		if ($taskid == "") { functions::throw_nack("taskid not set"); }
		if ($itemstopurge == "") { functions::throw_nack("itemstopurge not set"); }

		// group items by archive path
		foreach ($itemstopurge as $itemtopurge)
		{
			$taskinstanceid = $itemtopurge["instance_context"];
			unset($instances[$taskinstanceid]);
		}

		// store the updated version
		$string = json_encode($instances);
		$path = tasks::gettaskpath($taskid);
		$r = file_put_contents($path, $string, LOCK_EX);	

		$result["countafter"] = count($instances);
		
		return $result;
	}

	public static function appenditemstoarchive($taskid, $items_toarchive)
	{
		if ($taskid == "") { functions::throw_nack("taskid not set"); }
		if ($items_toarchive == "") { functions::throw_nack("items_toarchive not set"); }
		
		$items_by_archivepath = array();
		
		// group items by archive path
		foreach ($items_toarchive as $itemtoarchive)
		{
			// step 2.1;  grab creationdate of instance
			$createtime = $itemtoarchive["createtime"];
			
			// step 2.2;  determine archive path  (id + yyyy + mm of creationdate of instance.json)
			$path = tasks::getpath_archivedtaskinstances($taskid, $createtime);
			
			$items_by_archivepath[$path][] = $itemtoarchive;
		}
		
		foreach ($items_by_archivepath as $path => $items)
		{
			$subresult = tasks::appenditemstoarchivepath($taskid, $items, $path);
			$result["actions"][] = $subresult;
		}
		
		return $result;
	}

	public static function appenditemstoarchivepath($taskid, $items_to_append, $path)
	{
		$result = array();
		
		if ($path == "") { functions::throw_nack("path not set"); }
		if ($items_to_append == "") { functions::throw_nack("items_to_append not set"); }
		
		$result["items_to_append"] = count($items_to_append);
		
		// step 1; ensure the folder exists
		functions\filesystem::createcontainingfolderforfilepathifnotexists($path);
		
		// step 2; load existing items if there are any
		$items = array();
		if (file_exists($path))
		{
			$content = file_get_contents($path);
			$items = json_decode($content, true);
			if ($items == "")
			{
				// weird!
				functions::throw_nack("didnt expect items to be empty, please check whats going on ($path); invalid json?");
			}		
		}
		
		$result["items_in_archive_before"] = count($items);
		
		// blend the existing ones with the items to append
		foreach ($items_to_append as $item_to_append)
		{
			$taskinstanceid = $item_to_append["instance_context"];
			
			// double check this is the proper path
			$createtime = $item_to_append["createtime"];
			$check_path = tasks::getpath_archivedtaskinstances($taskid, $createtime);
			if ($path != $check_path) { functions::throw_nack("path mismatch? $path vs $check_path"); }

			$items[$taskinstanceid] = $item_to_append;
		}
		
		// store the updated version
		$string = json_encode($items);
		$r = file_put_contents($path, $string, LOCK_EX);
		
		if ($r === false) { functions::throw_nack("error updating task instance"); }
		
		$result["items_in_archive_after"] = count($items);
		$result["path"] = $path;
		
		return $result;
	}

	
	public static function wakeparentoftaskinstance($taskid, $taskinstanceid)
	{
		error_log("waking up parent");

		if ($taskid == "" || $taskinstanceid == "") { functions::throw_nack("taskid == empty || taskinstanceid == empty"); }
		
		$instancemeta = tasks::gettaskinstance($taskid, $taskinstanceid);
		
		$parent_taskid = $instancemeta["createdby_taskid"];
		$parent_taskinstanceid = $instancemeta["createdby_taskinstanceid"];
		if ($parent_taskid == "" || $parent_taskinstanceid == "") { functions::throw_nack("parent_taskid == empty || parent_taskinstanceid == empty"); }
		
		// if the parent instance is sleeping, consider waking it up
		$parentmeta = tasks::gettaskinstance($parent_taskid, $parent_taskinstanceid);
		
		// wake up parent
		$parentmeta["state"] = "STARTED";
		
		$parentmeta["wakeevents"][] = array
		(
			"creationtime" => time(),
			"wokenby_taskid" => $taskid,
			"wokenby_taskinstanceid" => $taskinstanceid,
		);
		tasks::updatetaskinstance($parent_taskid, $parent_taskinstanceid, $parentmeta);
	}

	public static function unarchive_archived_task_instance($taskid_to_unarchive, $taskinstanceid_to_unarchive)
	{
		$result = array();
		
		// 
		
		$findarchivedinstanceresult = tasks::archive_findarchivedinstance($taskid_to_unarchive, $taskinstanceid_to_unarchive);
		$result["findarchivedinstanceresult"] = $findarchivedinstanceresult;
		if ($findarchivedinstanceresult["isfound"])
		{
			$taskid = $taskid_to_unarchive;
			$taskinstanceid = $taskinstanceid_to_unarchive;
			
			$props = $findarchivedinstanceresult["props"];

			$path = tasks::gettaskpath($taskid);
			$string = file_get_contents($path);
			$meta = json_decode($string, true);
			
			if (isset($meta[$taskinstanceid]))
			{
				functions::throw_nack("error unarchiving archived task instance; instance {$taskinstanceid}@{$taskid} already exists?");
			}
			
			$meta[$taskinstanceid] = $props;
			$string = json_encode($meta);
			$r = file_put_contents($path, $string, LOCK_EX);
			if ($r === false) { functions::throw_nack("error unarchiving archived task instance; unable to write?"); }
			
			$result["countafter"] = count($meta);
		}
		else
		{
			functions::throw_nack("error unarchiving archived task instance; not found in archive;" . json_encode($findarchivedinstanceresult));
		}
		
		return $result;
	}

	public static function archive_findarchivedinstance($taskid, $taskinstanceid)
	{
		$result = array
		(
			"isfound" => false,
		);
		
		$containerpath = tasks::getarchivescontainerpath();
		$folderpaths = array_filter(glob("{$containerpath}*"), 'is_dir');
		foreach ($folderpaths as $folderpath)
		{
			$result["debug"][] = "considering:$folderpath";
			$archivename = basename($folderpath);
			$archivepath = "{$containerpath}{$archivename}/{$archivename}_archived_{$taskid}.json";
			if (file_exists($archivepath))
			{
				$archive_string = file_get_contents($archivepath);
				$archive_data = json_decode($archive_string, true);
				
				$count = count($archive_data);
				
				if (isset($archive_data[$taskinstanceid]))
				{
					$result["isfound"] = true;
					$result["props"] = $archive_data[$taskinstanceid];
					break;
				}
				else
				{
					$result["debug"][] = "archivename:$archivename";
					$result["debug"][] = "archivepath:$archivepath";
					$result["debug"][] = "items:$count";
				}			
			}
			else
			{
				$result["debug"][] = "archivepath not found; $archivepath";
			}
		}
		
		unset($result["debug"]);
		
		return $result;
	}

	public static function getpath_archivedtaskinstances($taskid, $createtime)
	{
		if ($taskid == "") { functions::throw_nack("taskid not set"); }
		
		if ($createtime == "") { functions::throw_nack("createtime not set"); }
		
		$yyyy = date("Y", $createtime);
		$mm = date("m", $createtime);
		
		$containerpath = tasks::getarchivescontainerpath();
		$result = "{$containerpath}{$yyyy}_{$mm}/{$yyyy}_{$mm}_archived_{$taskid}.json";
		return $result;
	}

	public static function getpossiblefilepaths($taskid)
	{
		$result = array();
		
		// the current task path is the first one to consider
		$result[] = tasks::gettaskpath($taskid);
		
		// followed by all subsequent archived ones, starting with the most recent one till the oldest one
		// the oldest one is from may 2019
		$containerpath = tasks::getarchivescontainerpath();
		
		$year_oldest_taskinstance = 2019;
		$lowest_month_taskinstance_in_oldest_year = 5;
		$heightestyear = date("Y");
		for ($year = $heightestyear; $year >= $year_oldest_taskinstance; $year--)
		{
			if ($year == $heightestyear)
			{
				$heighestmonthinyear = date('m');
				$lowestmonthinyear = 1;
			}
			else if ($year == $year_oldest_taskinstance)
			{
				$heighestmonthinyear = 12;
				$lowestmonthinyear = $lowest_month_taskinstance_in_oldest_year;
			}
			else
			{
				$heighestmonthinyear = 12;
				$lowestmonthinyear = 1;
			}
			
			for ($month = $heighestmonthinyear; $month >= $lowestmonthinyear; $month--)
			{
				$mm = str_pad($month, 2, "0", STR_PAD_LEFT);
				$yyyy = $year;
				
				$result[]= "{$containerpath}{$yyyy}_{$mm}/{$yyyy}_{$mm}_archived_{$taskid}.json";
			}
		}
		
		return $result;	
	}
	*/

	// query task instances / queries task instances
	public static function searchtaskinstances($args)
	{
		$return_this = $args["return_this"];
		
		$if_this = $args["if_this"];
		if ($if_this == null) { functions::throw_nack("tasks_searchtaskinstances; if_this not set in args"); }

		$homeurl = functions::geturlhome();

		$result["taskinstances"] = array();

		$taskids = tasks::gettaskids();
		foreach ($taskids as $taskid)
		{
			//echo "------\r\n";
			//echo "task: {$taskid}\r\n";
			//echo "instanceS:";
			$taskinstanceids = tasks::gettaskinstanceids($taskid);
			foreach ($taskinstanceids as $taskinstanceid)
			{
				$meta = tasks::gettaskinstance($taskid, $taskinstanceid);
			
				$evaluate_result = tasks::search_evaluate_if_this($if_this, $taskid, $meta);
				if ($evaluate_result["conclusion"] == true)
				{
					if (false)
					{
					}
					else if (!isset($return_this))
					{
						$result["taskinstances"][] = array
						(
							"taskid" => $taskid,
							"taskinstanceid" => $taskinstanceid,
							"url" => "{$homeurl}?nxs=task-gui&page=taskinstancedetail&taskid={$taskid}&taskinstanceid={$taskinstanceid}",
						);
					}
					else if ($return_this == "details")
					{
						$meta["taskid"] = $taskid;
						$meta["taskinstanceid"] = $taskinstanceid;
						$result["taskinstances"][] = $meta;
					}
					else
					{
						$result["taskinstances"][] = array
						(
							"taskid" => $taskid,
							"taskinstanceid" => $taskinstanceid,
							"url" => "{$homeurl}?nxs=task-gui&page=taskinstancedetail&taskid={$taskid}&taskinstanceid={$taskinstanceid}",
						);
					}
				}
			}
		}
		
		return $result;
	}
}