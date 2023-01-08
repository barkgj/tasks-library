<?php

function nxs_task_instance_do_send_theme_billing_reminder($then_that_item, $taskid, $taskinstanceid)
{
	//
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$state = $instancemeta["state"];
	$inputparameters = $instancemeta["inputparameters"];
	
	//
	$renew_licenseid = $inputparameters["renew_licenseid"];
	/*
	$hostname = $inputparameters["hostname"];
	if ($hostname == "") 
	{
  	$result["console"][] = "hostname is not set";
  	$result["result"] = "OK";
  	return $result;
	}
	*/
	
	$fetch_url = "https://license1802.nexusthemes.com/api/1/prod/licenseinsights/?nxs=licensemeta-api&nxs_json_output_format=prettyprint&licensenr={$renew_licenseid}";
	$fetch_string = file_get_contents($fetch_url);
	$license = json_decode($fetch_string, true);
	if ( $license["result"] != "OK") 
	{
  	$result["console"][] = "unable to fetch license detail";
  	$result["result"] = "NACK";
  	$result["nack_message"] = "error while fetching {$fetch_url}";
  	
  	return $result;
		  
	}
	$billing_email = $license["billing_email"];
	
	$old_expirationdate = $inputparameters["old_expirationdate"];
	
	$expirationdatehuman = date("j M Y", $old_expirationdate);

	// build up the renewurl
	if (true)
	{
		$order = array
		(
			"dspp_type" => "themelicenserenewal",
			"renew_licenseid" => $renew_licenseid,
			"oldexpirationdate" => $old_expirationdate,
			"newexpirationdate" => $old_expirationdate + 31622400,
			"taskid" => $taskid,
			"taskinstanceid" => $taskinstanceid,
		);
		$paymentlink = "http://nexusthemes.com/";
		$paymentlink = nxs_addqueryparametertourl_v2($paymentlink, "nxs_dspp_action", "set_cart_order", true, true);
		$paymentlink = nxs_addqueryparametertourl_v2($paymentlink, "order", json_encode($order), true, true);
	}
	
	// build up the mutelink
	if (true)
	{
		// see marker NXS.CODEMARKER.345634653456
		
		// get clientemail from license server
		
		$clientemail = $license["billing_email"];
		$time = time();
		
		$mutelink = "https://my.nexusthemes.com/";
		$mutelink = nxs_addqueryparametertourl_v2($mutelink, "nxs_public_portal_action", "mute_license_expiration_notifications", true, true);
		$mutelink = nxs_addqueryparametertourl_v2($mutelink, "licenseid", $renew_licenseid, true, true);
		$mutelink = nxs_addqueryparametertourl_v2($mutelink, "email", $clientemail, true, true);
		$mutelink = nxs_addqueryparametertourl_v2($mutelink, "time", $time, true, true);
		
		$checksum_input = "{$renew_licenseid}_{$clientemail}_{$time}";
		$checksum = md5($checksum_input);
		$mutelink = nxs_addqueryparametertourl_v2($mutelink, "checksum", $checksum, true, true);
	}
	
	//$result["console"][] = "renewurl url is; $renewurl";
	//$result["console"][] = "mute url is; $mutelink";
	
	$nonce_action = "send_mail_{$renew_licenseid}";
	$nonce_queryparameter = "_wpnonce";
	
	$should_do_it = false;
	if (nxs_tasks_isheadless())
	{
		$should_do_it = true;
	}
	else
	{
		if ($_REQUEST["send_mail"] == "true")
		{
			$should_do_it = true;
			$nonce = $_REQUEST[$nonce_queryparameter];
			$r = wp_verify_nonce($nonce, $nonce_action);			
			$result["console"][] = "nonce ($nonce) for $nonce_action:" . json_encode($r);
			
			if (!$r)
			{
				$result["console"][] = "unable to send mail; nonce verification failed";
		  	$result["result"] = "NACK";
		  	$result["nack_message"] = "unable to send mail; nonce verification failed";
		  	
		  	return $result;
			}
		}
	}
	
	if ($should_do_it)
	{
		// send out mail!
		
		$mail_url = "https://global.nexusthemes.com/api/1/prod/send-mail-template-for-license/?nxs=mail-api&nxs_json_output_format=prettyprint";
		
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "licenseid", $renew_licenseid, true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "mailtemplate", "60", true, true);
		// $mail_url = nxs_addqueryparametertourl_v2($mail_url, "hostname", $hostname, true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "expirationdatehuman", $expirationdatehuman, true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "paymentlink", $paymentlink, true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "mutelink", $mutelink, true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "expirationdatehuman", $expirationdatehuman, true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "invokedby_taskid", $taskid, true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "invokedby_taskinstanceid", $taskinstanceid, true, true);
		
		$mail_string = file_get_contents($mail_url);
		$mail_result = json_decode($mail_string, true);
		if ($mail_result["result"] != "OK")
		{
			$result["console"][] = "Error sending mail";
			$result["result"] = "NACK";
			$result["nack_details"] = array
			(
				"mail_url" => $mail_url,
				"mail_string" => $mail_string,
			);
		}
		
		$result["console"][] = "Mail is sent succesfully";
	}
	else
	{
		$currenturl = nxs_geturlcurrentpage();
		$send_mail_url = $currenturl;
		$send_mail_url = nxs_addqueryparametertourl_v2($send_mail_url, "send_mail", "true", true, true);
		$send_mail_url = wp_nonce_url($send_mail_url, $nonce_action, $nonce_queryparameter);
		
		$result["console"][] = "to send the mail use: <a href='{$send_mail_url}'>this link</a>";
	}
	
	$result["result"] = "OK";
	
	return $result;
}