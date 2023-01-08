<?php

function nxs_task_instance_encodespecialchars($input)
{
	$value = $input;
	
	// escape single quotes
	$value = str_replace("'", "__nxs:singlequote__", $value);
	// escape double quotes
	$value = str_replace("\"", "__nxs:doublequote__", $value);
	
	// escape <
	$value = str_replace("<", "__nxs:lt__", $value);
	// escape >
	$value = str_replace(">", "__nxs:gt__", $value);
	// escape \
	$value = str_replace("\\", "__nxs:backslash__", $value);
	
	return $value;
}

function nxs_task_instance_decodespecialchars($input)
{
	$value = $input;
	
	// escape single quotes
	$value = str_replace("__nxs:singlequote__", "'", $value);
	// escape double quotes
	$value = str_replace("__nxs:doublequote__", "\"", $value);
	// escape >
	$value = str_replace("__nxs:lt__", "<", $value);
	// escape <
	$value = str_replace("__nxs:gt__", ">", $value);
	// escape \
	$value = str_replace("__nxs:backslash__", "\\", $value);
	
	return $value;
}

function nxs_task_instance_do_sync_post($then_that_item, $taskid, $taskinstanceid)
{
	//$result["console"][] = "SYNC_POST";
	
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$inputparameters = nxs_tasks_gettaskinstancelookup($taskid, $taskinstanceid);
	
	// then_that_item keyvalues are stronger than inputparameters
	$args = wp_parse_args($then_that_item, $inputparameters);
	
	// 
	
	// validations
	foreach ($then_that_item as $then_that_key => $then_that_value)
	{
		if ($then_that_value == "{{{$then_that_key}}}")
		{
			$result["console"][] = "sync_post; ERR; you are doing it wrong; attributes ({then_that_key}) are not allowed to get a value with the same placeholder name ({{then_that_key}}); to fix this, prefix the value to {{i_{$then_that_key}}} (or any other prefix)";
			$result["result"] = "NACK";
			return $result;
		}
	}

	$realm = $args["p_realm"];
	$clientfacing_scheme = $args["p_clientfacing_scheme"];
	$clientfacing_hostname = $args["p_clientfacing_hostname"];
	$marktanalysisoutput_scheme = $args["p_marktanalysisoutput_scheme"];
	$marktanalysisoutput_hostname = $args["p_marktanalysisoutput_hostname"];
	
	$button_text = $then_that_item["button_text"];
	if ($button_text == "")
	{
		$button_text = "Sync post";
	}
	
	if (true)
	{
		$at_least_one_required_att_missing = false;
		$required_atts = array
		(
			"scheme",
			"hostname", 
			"post_category_slug", 
			"post_category_name",
			"post_featuredimage_filename",
			"post_featuredimage_subfolder_names",
			"post_featuredimage_url",
			"post_featuredimage_guid",
			"post_publicationdate_yyyymmdd",
			"post_slug",
			"post_title",
			"post_guid",
			"post_author_name",
			"post_author_guid",
			"post_focuskeyword"
		);
		foreach ($required_atts as $required_att)
		{
			if ($args[$required_att] == "")
			{
				$result["console"][] = "sync_post; ERR; {$required_att} attribute not set in shortcode";
				$at_least_one_required_att_missing = true;
			}	
		}
		
		if ($at_least_one_required_att_missing)
		{
			$result["result"] = "OK";
			return $result;
		}
	}
	
	
	$post_author_guid = $args["post_author_guid"];
	$post_comment_status = $args["post_comment_status"];
	
	$encoded_args = array();
	foreach ($args as $k => $v)
	{
		$encoded_args[$k] = nxs_task_instance_encodespecialchars($v);
		
	}
	
	// some fields can have single quotes which can/will cause troubles, we escape those (in a lame way, but it works ... )
	$escape_fields = array("post_content_json", "post_meta_json");
	foreach ($escape_fields as $escape_field)
	{
		$escape_field_val = $args[$escape_field];
		// first time
		$escape_field_val = nxs_filter_translatesingle($escape_field_val, "{{", "}}", $encoded_args);
		// second time
		$escape_field_val = nxs_filter_translatesingle($escape_field_val, "{{", "}}", $encoded_args);
		// encode weird chars (') into long form (__single quote__)
		// $escape_field_val = nxs_task_instance_encodespecialchars($escape_field_val);
		
		// put it back
		$args[$escape_field] = $escape_field_val;
	}
	
	// extend the post_meta with the task and taskinstance
	if (true)
	{
		$encoded_post_meta_json = $args["post_meta_json"];	// can /will/ contain __nxs:doublequote__
		$post_meta_json = nxs_task_instance_decodespecialchars($encoded_post_meta_json);
		
		//echo "step1:" . $post_meta_json . "<br />";
		
		$post_meta = json_decode($post_meta_json, true);
		$improved_post_meta = $post_meta;

		$improved_post_meta["nxs_syncedby_taskid"] = $taskid;
		$improved_post_meta["nxs_syncedby_taskinstanceid"] = $taskinstanceid;

		$improved_post_meta_json = json_encode($improved_post_meta);
		
		//echo "step2:" . $improved_post_meta_json . "<br />";
		
		$improved_post_meta_json = nxs_task_instance_encodespecialchars($improved_post_meta_json);
		
		//echo "step3:" . $improved_post_meta_json . "<br />";
		//die();
		
		$args["post_meta_json"] = $improved_post_meta_json;
	}
	
	foreach ($args as $k => $v)
	{
		$args[$k] = nxs_task_instance_encodespecialchars($v);
		$args[$k] = str_replace("\n", "__nxs:newline__", $args[$k]);
		$args[$k] = str_replace("\r", "__nxs:carriagereturn__", $args[$k]);
	}
	//
	
	$scheme = $args["scheme"];
	$hostname = $args["hostname"];
	// todo:rewrite, shouldbecome:
	//$marktanalysisoutput_scheme = $args["p_marktanalysisoutput_scheme"];
	//$marktanalysisoutput_hostname = $args["p_marktanalysisoutput_hostname"];
	
	$result["console"][] = "Sync post to {$scheme}://{$hostname}";
	$marker_id = "marker_after_sync";
	$return_url = "https://global.nexusthemes.com/?nxs=task-gui&page=taskinstancedetail&taskid={{taskid}}&taskinstanceid={{taskinstanceid}}#{$marker_id}";
	
	$postargs = array
	(
		"nxs" => "sync-wp-api",
		"action" => "sync_post",
		"taskid" => "{{taskid}}",
		"taskinstanceid" => "{{taskinstanceid}}",
		"post_author_guid" => "{{post_author_guid}}",
		"post_author_name" => "{{post_author_name}}",
		"post_guid" => "{{post_guid}}",
		"post_title" => "{{post_title}}",
		"post_slug" => "{{post_slug}}",
		"post_publicationdate_yyyymmdd" => "{{post_publicationdate_yyyymmdd}}",
		"post_featuredimage_guid" => "{{post_featuredimage_guid}}",
		"post_featuredimage_url" => "{{post_featuredimage_url}}",
		"post_featuredimage_filename" => "{{post_featuredimage_filename}}",
		"post_featuredimage_subfolder_names" => "{{post_featuredimage_subfolder_names}}",
		"post_featuredimage_alt" => "{{post_featuredimage_alt}}",
		"post_featuredimage_title" => "{{post_featuredimage_title}}",
		"post_category_name" => "{{post_category_name}}",
		"post_category_slug" => "{{post_category_slug}}",
		"post_meta_json" => "{{post_meta_json}}",
		"post_content_json" => "{{post_content_json}}",
		"post_focuskeyword" => "{{post_focuskeyword}}",
		"post_comment_status" => "{{post_comment_status}}",

		//
		"realm" => $realm,
		"clientfacing_scheme" => $clientfacing_scheme,
		"clientfacing_hostname" => $clientfacing_hostname,
		"marktanalysisoutput_scheme" => $marktanalysisoutput_scheme,
		"marktanalysisoutput_hostname" => $marktanalysisoutput_hostname,
	);
	// to replace any {{x}} placeholders, we first convert the array to a json string
	$post_args_jsonstring = json_encode($postargs);
	// then we replace the placeholders a first time
	$post_args_jsonstring = nxs_filter_translatesingle($post_args_jsonstring, "{{", "}}", $args);
	// then we replace the placeholders a second time
	$post_args_jsonstring = nxs_filter_translatesingle($post_args_jsonstring, "{{", "}}", $args); // second time on purpose

	// 
	$post_args_jsonstring = do_shortcode($post_args_jsonstring);
	
	// now convert the string back into an array 
	$postargs = json_decode($post_args_jsonstring, true);
	
	if ($postargs == false)
	{
		if (nxs_tasks_isheadless())
		{
			$result["console"][] = "postargs is false (incorrect json?) unable to proceed";
			$result["console"][] = "json: $post_args_jsonstring";
			$result["result"] = "NACK";
			return $result;
		}
		else
		{
			$result["console"][] = "sync_post; unable to proceed; postargs is false; json is not valid?";
			$result["console"][] = "json: $post_args_jsonstring";
			$result["console"][] = "<span id='{$marker_id}'></span>";
			$result["result"] = "OK";
			return $result;
		}
	}
	
	if ($postargs == null)
	{
		if (nxs_tasks_isheadless())
		{
			$result["console"][] = "postargs is null (incorrect json?) unable to proceed";
			$result["result"] = "NACK";
			return $result;
		}
		else
		{
			$result["console"][] = "sync_post; unable to proceed; postargs is null; json is not valid?";
			$result["console"][] = "<span id='{$marker_id}'></span>";
			$result["result"] = "OK";
			return $result;
		}
	}
	
	// loop through the post_content_json to performs checks like whether url's exist or not
		
	$url = "{$scheme}://{$hostname}";
	
	$url = nxs_filter_translatesingle($url, "{{", "}}", $inputparameters);
	
	/*
	if ($_REQUEST["debugme"] == "true")
	{
		echo "post_content_json:";
		$t = $args["post_content_json"];
		$t = str_replace("\r", "SLASH_R", $t);
		$t = str_replace("\n", "SLASH_N", $t);
		
		echo $t;
		
		echo "\r\n\r\n";
		
		die();
	}
	*/
	
	if (nxs_tasks_isheadless())
	{
		// automated
		// post the form
		$args = array
		(
			"url" => $url,
			"method" => "POST",
			"postargs" => $postargs,
		);
		
		$result["console"][] = "about to post form to $url";
		
		$delegated_result_string = nxs_geturlcontents($args);
		
		$result["console"][] = "post result:";
		$result["console"][] = $delegated_result_string;
		
		$delegated_result = json_decode($delegated_result_string, true);
				
		if ($delegated_result["result"] != "OK")
		{
			$result["console"][] = "form post returned NACK";
			$result["result"] = "NACK";
			return $result;
		}
		else
		{
			$result["console"][] = "form post returned OK";
		}		
	}
	else
	{
		// gui
		
		$html = "";
		$html .= "<form action='{$url}' method='POST'>";
		foreach ($postargs as $postarg_key => $postarg_val)
		{
			$html .= "<input type='hidden' name='{$postarg_key}' value='{$postarg_val}' />";
		}
		// one additional input parameter for 'gui' request; the returnurl is then also specified
		$html .= "<input type='hidden' name='{$return_url}' value='{$return_url}' />";
		$html .= "<input type='submit' value='{$button_text}' />";
		$html .= "</form>";
	
		// remove noise from template, BEFORE putting in parameters
		$html = str_replace("\r", "", $html);
		$html = str_replace("\n", "", $html);
		
		$result["console"][] = $html;
		$result["console"][] = "<span id='{$marker_id}'></span>";
	}
		
	$result["result"] = "OK";
	
	return $result;
}