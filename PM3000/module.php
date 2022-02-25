<?php

// Klassendefinition
class PM3000 extends IPSModule {
 
	// Der Konstruktor des Moduls
	// Überschreibt den Standard Kontruktor von IPS
	public function __construct($InstanceID) {
		// Diese Zeile nicht löschen
		parent::__construct($InstanceID);

		// Selbsterstellter Code
		// Define all the data
		$this->snmpVariables = Array(
			Array("ident" => "Hostname", 			"caption" => "Hostname", 			"type" => "String", "profile" => false, "oid" => '.1.3.6.1.4.1.10418.17.2.1.1.0', "factor" => "", "writeable" => false),
			Array("ident" => "Model", 				"caption" => "Model", 				"type" => "String", "profile" => false, "oid" => '.1.3.6.1.4.1.10418.17.2.1.1.0', "factor" => "", "writeable" => false),
			Array("ident" => "SerialNumber", 		"caption" => "Serial Number", 		"type" => "String", "profile" => false, "oid" => '.1.3.6.1.4.1.10418.17.2.1.1.0', "factor" => "", "writeable" => false),
			Array("ident" => "BootcodeVersion", 	"caption" => "Bootcode Version", 	"type" => "String", "profile" => false, "oid" => '.1.3.6.1.4.1.10418.17.2.1.1.0', "factor" => "", "writeable" => false),
			Array("ident" => "FirmwareVersion", 	"caption" => "Firmware Version", 	"type" => "String", "profile" => false, "oid" => '.1.3.6.1.4.1.10418.17.2.1.1.0', "factor" => "", "writeable" => false)
		);
	}
 
	// Überschreibt die interne IPS_Create($id) Funktion
	public function Create() {
		
		// Diese Zeile nicht löschen.
		parent::Create();

		// Properties
		$this->RegisterPropertyString("Sender","PM3000");
		$this->RegisterPropertyInteger("RefreshInterval",0);
		$this->RegisterPropertyInteger("SnmpInstance",0);
		$this->RegisterPropertyBoolean("DebugOutput",false);
		
		// Variables
		$stringVariables = $this->GetVariablesByType("String");
		foreach ($stringVariables as $currentVariable) {

			if ($currentVariable['profile']) {

				$this->RegisterVariableString($currentVariable['ident'], $currentVariable['caption'], $currentVariable['profile']);
			}
			else {

				$this->RegisterVariableString($currentVariable['ident'], $currentVariable['caption']);
			}
		}
		
		$this->RegisterVariableFloat("InternalTemperature", "Internal Temperature Sensor", "~Temperature");
		$this->RegisterVariableFloat("TotalCurrent", "Total Current", "~Ampere.16");
		$this->RegisterVariableFloat("TotalPower", "Total Power", "~Watt.3680");
		$this->RegisterVariableFloat("TotalVoltage", "Total Voltage", "~Volt.230");

		$this->RegisterVariableInteger("NumberOfOutlets", "Number of Outlets");
		$this->RegisterVariableInteger("ColdStartDelay", "Cold Start Delay", "~TimePeriodSec.KNX");
		$this->RegisterVariableInteger("TotalEnergy", "Total Energy", "~ActiveEnergy.KNX");

		// Timer
		$this->RegisterTimer("RefreshInformation", 0, 'PM3000_RefreshInformation($_IPS[\'TARGET\']);');

	}

        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
    public function ApplyChanges() {

		$newInterval = $this->ReadPropertyInteger("RefreshInterval") * 1000;
		$this->SetTimerInterval("RefreshInformation", $newInterval);

		// Editable values
		$this->EnableAction("ColdStartDelay");

		// Diese Zeile nicht löschen
		parent::ApplyChanges();
	}

	public function RequestAction($Ident, $Value) {
	
	
		switch ($Ident) {
		
			case "ColdStartDelay":
				IPSSNMP_WriteSNMPbyOID($this->ReadPropertyInteger("SnmpInstance"), '.1.3.6.1.4.1.10418.17.2.5.3.1.42.1.1', $Value, 'i');
				SetValue($this->GetIDForIdent($Ident), $Value);
				break;
			default:
				$this->LogMessage("Invalid Ident: $Ident","CRIT");
		}
	}

	public function GetConfigurationForm() {

        	
		// Initialize the form
		$form = Array(
            		"elements" => Array(),
					"actions" => Array()
        		);

		// Add the Elements
		$form['elements'][] = Array("type" => "NumberSpinner", "name" => "RefreshInterval", "caption" => "Refresh Interval");
		$form['elements'][] = Array("type" => "CheckBox", "name" => "DebugOutput", "caption" => "Enable Debug Output");
		$form['elements'][] = Array("type" => "SelectInstance", "name" => "SnmpInstance", "caption" => "SNMP instance");

		// Add the buttons for the test center
        $form['actions'][] = Array("type" => "Button", "label" => "Refresh Overall Status", "onClick" => 'PM3000_RefreshInformation($id);');

		// Return the completed form
		return json_encode($form);

	}


        /**
	* Get the list of robots linked to this profile and modifies the Select list to allow the user to select them.
        *
        */
    public function RefreshInformation() {

		$oid_mapping_table = $this->GetOidMappingTable();
		$oid_mapping_table['InternalTemperature'] 	= '.1.3.6.1.4.1.10418.17.2.5.13.1.25.1.1.1';
		$oid_mapping_table['NumberOfOutlets'] 		= '.1.3.6.1.4.1.10418.17.2.5.3.1.8.1.1';
		$oid_mapping_table['ColdStartDelay'] 		= '.1.3.6.1.4.1.10418.17.2.5.3.1.42.1.1';
		// Add Alarm OID here
		$oid_mapping_table['TotalCurrent'] 			= '.1.3.6.1.4.1.10418.17.2.5.3.1.50.1.1';
		$oid_mapping_table['TotalPower'] 			= '.1.3.6.1.4.1.10418.17.2.5.3.1.60.1.1';
		$oid_mapping_table['TotalVoltage'] 			= '.1.3.6.1.4.1.10418.17.2.5.3.1.70.1.1';
		$oid_mapping_table['TotalEnergy'] 			= '.1.3.6.1.4.1.10418.17.2.5.3.1.105.1.1';

		$factor_mapping_table['InternalTemperature'] 	= 0.1;
		$factor_mapping_table['TotalCurrent'] 			= 0.1;
		$factor_mapping_table['TotalPower'] 			= 0.1;

		$this->UpdateVariables($oid_mapping_table, $factor_mapping_table);
	}

	// Version 1.0
	protected function LogMessage($message, $severity = 'INFO') {
		
		$logMappings = Array();
		// $logMappings['DEBUG'] 	= 10206; Deactivated the normal debug, because it is not active
		$logMappings['DEBUG'] 	= 10201;
		$logMappings['INFO']	= 10201;
		$logMappings['NOTIFY']	= 10203;
		$logMappings['WARN'] 	= 10204;
		$logMappings['CRIT']	= 10205;
		
		if ( ($severity == 'DEBUG') && ($this->ReadPropertyBoolean('DebugOutput') == false )) {
			
			return;
		}
		
		$messageComplete = $severity . " - " . $message;
		parent::LogMessage($messageComplete, $logMappings[$severity]);
	}
		
	
	protected function UpdateVariables($oids, $factors) {
	
		$result = $this->SnmpGet($oids);
		
		foreach ($oids as $varIdent => $varOid) {
		
			if (array_key_exists($varIdent, $factors)) {

				$this->LogMessage("Using Conversion Factor " . $factors[$varIdent] . " for Ident $varIdent", "DEBUG");

				SetValue($this->GetIDForIdent($varIdent), $result[$varOid] * $factors[$varIdent]);
			}
			else {
			
				SetValue($this->GetIDForIdent($varIdent), $result[$varOid]);
			}
		}
	}

	protected function SnmpGet($oids) {
	
		$result = IPSSNMP_ReadSNMP($this->ReadPropertyInteger("SnmpInstance"), $oids);	
		
		if (count($result) == 0) {

			$this-LogMessage("Unable to retrieve information via SNMP","CRIT");
			$this->SetStatus(200);
			return false;
		}
		else {

			$this->SetStatus(102);
		}

		$this->LogMessage("Number of SNMP entries found: " . count($result), "DEBUG");

		return $result;
	}

	protected function GetVariablesByType($type) {

		$variables = Array();

		foreach ($this->snmpVariables as $currentVariable) {

			if($currentVariable['type'] == $type) {

				$variables[]['ident'] 	= $currentVariable['ident'];
				$variables[]['caption'] = $currentVariable['caption'];
				$variables[]['profile'] = $currentVariable['profile'];
			}
		}

		return $variables;
	}

	protected function GetOidMappingTable() {

		$mappingTable = Array();

		foreach ($this->snmpVariables as $currentVariable) {
		
			$mappingTable[$currentVariable['idnet']] = $currentVariable['oid'];
		}

		return $mappingTable;
	}
}