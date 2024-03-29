<?php

include_once __DIR__ . '/../libs/vendor/autoload.php';
use FreeDSx\Snmp\SnmpClient;
use FreeDSx\Snmp\Exception\SnmpRequestException;
use FreeDSx\Snmp\Oid;

// Klassendefinition
class PM3000Outlet extends IPSModule {
 
	// Der Konstruktor des Moduls
	// Überschreibt den Standard Kontruktor von IPS
	public function __construct($InstanceID) {
		// Diese Zeile nicht löschen
		parent::__construct($InstanceID);

		// Selbsterstellter Code
		// Define all the data
		$this->snmpVariables = Array(
			Array("ident" => "Name", 			"caption" => "Name", 					"type" => "String", 	"profile" => false, 				"oid" => '1.3.6.1.4.1.10418.17.2.5.5.1.4.1.1', 	"factor" => false, 	"writeable" => false),
			Array("ident" => "PostOnDelay", 	"caption" => "Post On Delay", 			"type" => "Integer", 	"profile" => "~TimePeriodSec.KNX", 	"oid" => '1.3.6.1.4.1.10418.17.2.5.5.1.9.1.1', 	"factor" => 0.1, 	"writeable" => true ),
			Array("ident" => "PostOffDelay", 	"caption" => "Post Off Delay", 			"type" => "Integer", 	"profile" => "~TimePeriodSec.KNX", 	"oid" => '1.3.6.1.4.1.10418.17.2.5.5.1.10.1.1', 	"factor" => 0.1, 	"writeable" => true ),
			Array("ident" => "AlarmStatus", 	"caption" => "Alarm Status", 			"type" => "Integer", 	"profile" => "PM3000.AlarmState", 	"oid" => '1.3.6.1.4.1.10418.17.2.5.5.1.45.1.1', 	"factor" => false, 	"writeable" => false),
			Array("ident" => "Current", 		"caption" => "Current", 				"type" => "Float", 		"profile" => "~Ampere.16", 			"oid" => '1.3.6.1.4.1.10418.17.2.5.5.1.50.1.1', 	"factor" => 0.1, 	"writeable" => false),
			Array("ident" => "Power", 			"caption" => "Power", 					"type" => "Float", 		"profile" => "~Watt.3680", 			"oid" => '1.3.6.1.4.1.10418.17.2.5.5.1.60.1.1', 	"factor" => 0.1, 	"writeable" => false),
			Array("ident" => "Voltage", 		"caption" => "Voltage", 				"type" => "Float", 		"profile" => "~Volt.230", 			"oid" => '1.3.6.1.4.1.10418.17.2.5.5.1.70.1.1', 	"factor" => false, 	"writeable" => false),
			Array("ident" => "Energy", 			"caption" => "Energy", 					"type" => "Integer", 	"profile" => "~ActiveEnergy.KNX", 	"oid" => '1.3.6.1.4.1.10418.17.2.5.5.1.105.1.1', 	"factor" => false, 	"writeable" => false),
			Array("ident" => "PowerStatus", 	"caption" => "Power Status", 			"type" => "Integer", 	"profile" => "PM3000.PowerStatus", 	"oid" => '1.3.6.1.4.1.10418.17.2.5.5.1.5.1.1', 	"factor" => false, 	"writeable" => false),
			Array("ident" => "PowerControl", 	"caption" => "Power Control", 			"type" => "Integer", 	"profile" => "PM3000.PowerControl", "oid" => '1.3.6.1.4.1.10418.17.2.5.5.1.6.1.1', 	"factor" => false, 	"writeable" => true )
		);
	}
 
	// Überschreibt die interne IPS_Create($id) Funktion
	public function Create() {
		
		// Diese Zeile nicht löschen.
		parent::Create();

		// Properties
		$this->RegisterPropertyString("Sender","PM3000Outlet");
		$this->RegisterPropertyInteger("RefreshInterval",0);
		$this->RegisterPropertyInteger("SnmpInstance",0);
		$this->RegisterPropertyInteger("OutletIndex",0);
		$this->RegisterPropertyBoolean("DebugOutput",false);
		$this->RegisterPropertyString("Hostname","");
		$this->RegisterPropertyString("Community","");

		// Variable profiles
		$variableProfilePowerStatus = "PM3000.PowerStatus";
		if (IPS_VariableProfileExists($variableProfilePowerStatus) ) {
		
			IPS_DeleteVariableProfile($variableProfilePowerStatus);
		}			
		IPS_CreateVariableProfile($variableProfilePowerStatus, 1);
		IPS_SetVariableProfileIcon($variableProfilePowerStatus, "Electricity");
		IPS_SetVariableProfileAssociation($variableProfilePowerStatus, 1, "Off", "", -1);
		IPS_SetVariableProfileAssociation($variableProfilePowerStatus, 2, "On", "", 0x00FF00);
		IPS_SetVariableProfileAssociation($variableProfilePowerStatus, 3, "Off / Locked", "", -1);
		IPS_SetVariableProfileAssociation($variableProfilePowerStatus, 4, "On / Locked", "", 0x00FF00);
		IPS_SetVariableProfileAssociation($variableProfilePowerStatus, 5, "Off / Cycling", "", -1);
		IPS_SetVariableProfileAssociation($variableProfilePowerStatus, 6, "On / Pending Off", "", 0x00FF00);
		IPS_SetVariableProfileAssociation($variableProfilePowerStatus, 7, "Off / Pending On", "", -1);
		IPS_SetVariableProfileAssociation($variableProfilePowerStatus, 8, "On / Pending Cycle", "", 0x00FF00);
		IPS_SetVariableProfileAssociation($variableProfilePowerStatus, 10, "On / Fixed", "", 0x00FF00);
		IPS_SetVariableProfileAssociation($variableProfilePowerStatus, 11, "Off / Shutdown", "", -1);
		IPS_SetVariableProfileAssociation($variableProfilePowerStatus, 12, "Tripped", "", 0xFF0000);

		$variableProfilePowerControl = "PM3000.PowerControl";
		if (IPS_VariableProfileExists($variableProfilePowerControl) ) {
		
			IPS_DeleteVariableProfile($variableProfilePowerControl);
		}			
		IPS_CreateVariableProfile($variableProfilePowerControl, 1);
		IPS_SetVariableProfileIcon($variableProfilePowerControl, "Power");
		IPS_SetVariableProfileAssociation($variableProfilePowerControl, 1, "No Action", "", -1);
		IPS_SetVariableProfileAssociation($variableProfilePowerControl, 2, "Power On", "", 0x00FF00);
		IPS_SetVariableProfileAssociation($variableProfilePowerControl, 3, "Power Off", "", 0xFF0000);
		IPS_SetVariableProfileAssociation($variableProfilePowerControl, 4, "Power Cycle", "", 0xFFFF00);
		IPS_SetVariableProfileAssociation($variableProfilePowerControl, 5, "Lock Outlet", "", -1);
		IPS_SetVariableProfileAssociation($variableProfilePowerControl, 6, "Unlock Outlet", "", -1);
	
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

		$stringVariables = $this->GetVariablesByType("Float");
		foreach ($stringVariables as $currentVariable) {

			if ($currentVariable['profile']) {

				$this->RegisterVariableFloat($currentVariable['ident'], $currentVariable['caption'], $currentVariable['profile']);
			}
			else {

				$this->RegisterVariableFloat($currentVariable['ident'], $currentVariable['caption']);
			}
		}
		
		$stringVariables = $this->GetVariablesByType("Integer");
		foreach ($stringVariables as $currentVariable) {

			if ($currentVariable['profile']) {

				$this->RegisterVariableInteger($currentVariable['ident'], $currentVariable['caption'], $currentVariable['profile']);
			}
			else {

				$this->RegisterVariableInteger($currentVariable['ident'], $currentVariable['caption']);
			}
		}
		
		// Timer
		$this->RegisterTimer("RefreshInformation", 0, 'PM3000OUTLET_RefreshInformation($_IPS[\'TARGET\']);');

	}

        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
    public function ApplyChanges() {

		$newInterval = $this->ReadPropertyInteger("RefreshInterval") * 1000;
		$this->SetTimerInterval("RefreshInformation", $newInterval);

		// Editable values
		$writeableIdents = $this->GetWriteableVariableIdents();
		if (count($writeableIdents) > 0) {

			foreach ($writeableIdents as $currentIdent) {

				$this->EnableAction($currentIdent);
			}
		}

		// Diese Zeile nicht löschen
		parent::ApplyChanges();
	}

	public function RequestAction($Ident, $Value) {
	
		// Route values based on dynamic arrays
		$writeableIdents = $this->GetWriteableVariableIdents();
		$this->LogMessage("Found " . count($writeableIdents) . " writable idents: " . implode(', ', $writeableIdents),"DEBUG");

		if (count($writeableIdents) > 0) {

			if (in_array($Ident, $writeableIdents)) {

				$this->SetWriteableVariable($Ident, $Value);
				SetValue($this->GetIDForIdent($Ident), $Value);

				$this->RefreshInformation();
				return;
			}
		}
	
		// Fallback for static defined idents
		switch ($Ident) {
		
			default:
				$this->LogMessage("Invalid Ident: $Ident","CRIT");
		}

		// Refresh after changing something
		$this->RefreshInformation();
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
		$form['elements'][] = Array("type" => "ValidationTextBox", "name" => "Hostname", "caption" => "SNMP host");
		$form['elements'][] = Array("type" => "ValidationTextBox", "name" => "Community", "caption" => "SNMP community");
		$form['elements'][] = Array("type" => "NumberSpinner", "name" => "OutletIndex", "caption" => "Outlet Index");

		// Add the buttons for the test center
        $form['actions'][] = Array("type" => "Button", "label" => "Refresh Overall Status", "onClick" => 'PM3000OUTLET_RefreshInformation($id);');

		// Return the completed form
		return json_encode($form);

	}


        /**
	* Get the list of robots linked to this profile and modifies the Select list to allow the user to select them.
        *
        */
    public function RefreshInformation() {

		$oid_mapping_table 		= $this->GetOidMappingTable();
		$factor_mapping_table 	= $this->GetFactorMappingTable();

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

				$variables[] = $currentVariable;
			}
		}

		return $variables;
	}

	protected function GetOidMappingTable() {

		$mappingTable = Array();

		foreach ($this->snmpVariables as $currentVariable) {
		
			$mappingTable[$currentVariable['ident']] = $currentVariable['oid'] . "." . $this->ReadPropertyInteger("OutletIndex");
		}

		return $mappingTable;
	}

	protected function GetFactorMappingTable() {

		$mappingTable = Array();

		foreach ($this->snmpVariables as $currentVariable) {
		
			if ($currentVariable['factor']) {
			
				$mappingTable[$currentVariable['ident']] = $currentVariable['factor'];
			}
		}

		return $mappingTable;
	}

	protected function GetWriteableVariableIdents() {

		$idents = Array();

		foreach ($this->snmpVariables as $currentVariable) {
		
			if ($currentVariable['writeable']) {
			
				$idents[] = $currentVariable['ident'];
			}
		}

		return $idents;
	}

	protected function SetWriteableVariable($ident, $value) {

		foreach ($this->snmpVariables as $currentVariable) {
		
			if ($currentVariable['ident'] == $ident) {
			
				$oid = $currentVariable['oid'] . "." . $this->ReadPropertyInteger("OutletIndex");
				if ($currentVariable['type'] == 'String') {
					
					$type = 's';
				}
				else {

					$type = 'i';
				}
				if ($currentVariable['factor']) {

					$value = $value * (1 / $currentVariable['factor']);
				}
			}
		}

		$this->LogMessage("Sending Value $value with Type $type to OID: $oid", "DEBUG");
		
		$snmp = $this->createSNMPClient();
		try {

			if ($type == 's') {

				$snmp->set(Oid::fromString($oid, $value));
			}
			else {

				
				$snmp->set(Oid::fromInteger($oid, $value));
			}
		} catch (SnmpRequestException $e) {
		
			$this->LogMessage("SNMP request failed " . $e->getMessage(), "CRIT");
			return false;
		}
	}

	private function createSNMPClient()
    {
        $configuration = [
            'host'      => $this->ReadPropertyString('Hostname'),
			'community' => $this->ReadPropertyString('Community'),
            'version'   => 2,
        ];
        
        return new SnmpClient($configuration);
    }
}