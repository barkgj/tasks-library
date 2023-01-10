<?php

use barkgj\functions;
use barkgj\tasks;
use barkgj\tasks\itaskinstruction;

class set_stateparameter implements itaskinstruction
{	
	public $_taskid;
	public $_taskinstanceid;
	public $_taskinstructionprops;
	
	public function __construct($taskid, $taskinstanceid, $taskinstructionprops)
	{
		$this->_taskid = $taskid;
		$this->_taskinstanceid = $taskinstanceid;
		$this->_taskinstructionprops = $taskinstructionprops;
	}

	function execute()
	{
		$result["console"][] = "SET INPUT PARAMETER TO VALUE";
		
		$key = $this->_taskinstructionprops["key"];
		$value = $this->_taskinstructionprops["value"];
		
		tasks::appendinputparameter_for_taskinstance($this->_taskid, $this->_taskinstanceid, $key, $value);

		$result["console"][] = "SET VALUE OF $key TO $value";
		$result["result"] = "OK";
		
		return $result;
	}
}