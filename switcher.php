#!/usr/bin/php
<?php
//error_reporting(0);
include 'php_serial.class.php';
include_once('switcherCommands.inc');

$skipJSsettings = 1;

$fppWWWPath = '/opt/fpp/www/';
set_include_path(get_include_path() . PATH_SEPARATOR . $fppWWWPath);

require("common.php");

$pluginName  = "Switcher";

include_once 'functions.inc.php';
include_once 'commonFunctions.inc.php';

$pluginConfigFile = $settings['configDirectory'] . "/plugin." .$pluginName;
if (file_exists($pluginConfigFile))
	$pluginSettings = parse_ini_file($pluginConfigFile);

$logFile = $settings['logDirectory'] . "/".$pluginName.".log";
$myPid = getmypid();

$DEBUG=false;
$SERIAL_DEVICE="";
$callBackPid="";

//$DEVICE = ReadSettingFromFile("DEVICE",$pluginName);
$DEVICE = $pluginSettings['DEVICE'];

//$DEVICE_CONNECTION_TYPE = ReadSettingFromFile("DEVICE_CONNECTION_TYPE",$pluginName);
$DEVICE_CONNECTION_TYPE = $pluginSettings['DEVICE_CONNECTION_TYPE'];

//$IP = ReadSettingFromFile("IP",$pluginName);
$IP = $pluginSettings['IP'];

//$PORT = ReadSettingFromFile("PORT",$pluginName);
$PORT = $pluginSettings['PORT'];

//$ENABLED = ReadSettingFromFile("ENABLED",$pluginName);
$ENABLED = $pluginSettings['ENABLED'];

//$SWITCHER = urldecode(ReadSettingFromFile("SWITCHER",$pluginName));
$SWITCHER = urldecode($pluginSettings['SWITCHER']);
$IP = urldecode($pluginSettings['IP']);
$PROJ_PASSWORD = urldecode($pluginSettings['PROJ_PASSWORD']);

logEntry("SWITCHER: ".$SWITCHER);

if(trim($SWITCHER == "" )) {
	logEntry("No Switcher configured in plugin, exiting");
	exit(0);
	
}

$options = getopt("c:");


$SERIAL_DEVICE="/dev/".$DEVICE;



logEntry("option C: ".$options["c"]);
$cmd= strtoupper(trim($options["c"]));

//loop through the array of SWITCHERS to get the command
$switcherIndex = 0;
//set the found flag, do not send a command if the name and command cannot be found


//print_r($SWITCHERS);
$SWITCHER_FOUND=false;

for($switcherIndex=0;$switcherIndex<=count($SWITCHERS)-1;$switcherIndex++) {

	
	if($SWITCHERS[$switcherIndex]['NAME'] == $SWITCHER) {
			logEntry("Switcher index: ".$switcherIndex);
			logEntry("Looking for command string for cmd: ".$cmd);

			while (list($key, $val) = each($SWITCHERS[$switcherIndex])) {
				//logEntry( "Key: ".$key. "  \t VAL:".$val);

				if(strtoupper(trim($key)) == $cmd) {
					$SWITCHER_FOUND=true;
					$SWITCHER_CMD = $val;
					
					logEntry("--------------");
					logEntry("SWITCHER FOUND");
					logEntry("SWITCHER: ".$SWITCHER_FOUND);
					
					
						
						if($pluginSettings['BAUD_RATE'] !="")
						{
							$SWITCHER_BAUD = $pluginSettings['BAUD_RATE'];
						} else {
							$SWITCHER_BAUD=$SWITCHERS[$switcherIndex]['BAUD_RATE'];
						}

						if($pluginSettings['CHAR_BITS'] !="")
						{
							$SWITCHER_CHAR_BITS = $pluginSettings['CHAR_BITS'];
						} else {
							$SWITCHER_CHAR_BITS=$SWITCHERS[$switcherIndex]['CHAR_BITS'];
						}

						if($pluginSettings['STOP_BITS'] !="")
						{
							$SWITCHER_STOP_BITS = $pluginSettings['STOP_BITS'];
						} else {
							$SWITCHER_STOP_BITS=$SWITCHERS[$switcherIndex]['STOP_BITS'];
						}

						if($pluginSettings['PARITY'] !="")
						{
							$SWITCHER_PARITY = $pluginSettings['PARITY'];
						} else {
							$SWITCHER_PARITY=$SWITCHERS[$switcherIndex]['PARITY'];
						}
						
					
						logEntry("BAUD RATE: ".$SWITCHER_BAUD);
						logEntry("CHAR BITS: ".$SWITCHER_CHAR_BITS);
						logEntry("STOP BITS: ".$SWITCHER_STOP_BITS);
						logEntry("PARITY: ".$SWITCHER_PARITY);
						
						
				}	
			}
		}
	
}


if(!$SWITCHER_FOUND) {
	logEntry("switcher command not found: exiting");
	exit(0);
}

$cmd = explode(",",$SWITCHER_CMD);
//print_r($cmd);

$i=0;
for($i=0;$i<=count($cmd);$i++) {
	$switcher_cmd .= chr($cmd[$i]);
}

$SWITCHER_CMD = $switcher_cmd;

logEntry("-------");
logEntry("Sending command");
logEntry("Switcher cmd: ".$SWITCHER_CMD);

	logEntry("Sending SERIAL COMMAND");
	logEntry("SERIAL DEVICE: ".$SERIAL_DEVICE);
        $serial = new phpSerial;

        $serial->deviceSet($SERIAL_DEVICE);
        $serial->confBaudRate($SWITCHER_BAUD);
        $serial->confParity($SWITCHER_PARITY);
//        $serial->confFlowControl("none");
        $serial->confCharacterLength($SWITCHER_CHAR_BITS);
        $serial->confStopBits($SWITCHER_STOP_BITS);
        $serial->deviceOpen();
		
	
	$serial->sendMessage($SWITCHER_CMD);
	sleep(1);
	logEntry("RETURN DATA: ".hex_dump($serial->readPort()));
	logEntry("RETURN DATA: ".$serial->readPort());
	$serial->deviceClose();
	
?>
