<?php

function nxs_task_instance_do_send_reseller_billing_reminder($then_that_item, $taskid, $taskinstanceid)
{
	//
	$instancemeta = nxs_task_getinstance($taskid, $taskinstanceid);
	$state = $instancemeta["state"];
	
	if ($state == "STARTED")
	{
		$inputparameters = $instancemeta["inputparameters"];
		
		//
		$renew_licenseid = $inputparameters["renew_licenseid"];
		$hostname = $inputparameters["hostname"];
		if ($hostname == "") 
		{
	  	$result["console"][] = "hostname is not set";
	  	$result["result"] = "OK";
	  	return $result;
		}
		
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
				"dspp_type" => "hostinglicenserenewal",
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
			
			$checksum_input = "{$licenseid}_{$clientemail}_{$time}";
			$checksum = md5($checksum_input);
			$mutelink = nxs_addqueryparametertourl_v2($mutelink, "checksum", $checksum, true, true);
		}
		
		//$result["console"][] = "renewurl url is; $renewurl";
		//$result["console"][] = "mute url is; $mutelink";
		
		$mail_url = "https://global.nexusthemes.com/api/1/prod/send-mail-template-for-license/?nxs=mail-api&nxs_json_output_format=prettyprint";
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "licenseid", $renew_licenseid, true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "mailtemplate", "149", true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "hostname", $hostname, true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "paymentlink", $paymentlink, true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "mutelink", $mutelink, true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "expirationdatehuman", $expirationdatehuman, true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "invokedby_taskid", $taskid, true, true);
		$mail_url = nxs_addqueryparametertourl_v2($mail_url, "invokedby_taskinstanceid", $taskinstanceid, true, true);
		
		$whatmakesthisunique = $mail_url;
		$enabler = md5($whatmakesthisunique);
		
		$doit = false;
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
		
		if ($doit)
		{
			$result["console"][] = "Enqueue-ing sending mail for license";
			
			$mail_string = file_get_contents($mail_url);
			$mail_result = json_decode($mail_string, true);
			if ($mail_result["result"] != "OK")
			{
				$result["console"][] = "Error enqueue-ing mail";
				$result["result"] = "NACK";
				$result["nack_details"] = array
				(
					"mail_url" => $mail_url,
					"mail_string" => $mail_string,
				);
			}
			
			$result["console"][] = "Mail is sending enqueued";
		}
		else
		{
			$currenturl = nxs_geturlcurrentpage();
	  	$action_url = $currenturl;
			$action_url = nxs_addqueryparametertourl_v2($action_url, "doit", $enabler, true, true);
			$result["console"][] = "<span style='background-color: orange; color: white;'>NOTE: procrastinating</span> the creation of 33 instance to send the mail because the shortcode is invoked by the GUI (not headless through a workflow/batch)";
			$result["console"][] = "<a href='{$action_url}#$marker'>Click here to create 33 instance to send email</a>";
		}
	}
	else
  {
  	$result["console"][] = "not doing anything because of state $state";	
  }
	
	$result["result"] = "OK";
	
	return $result;
}