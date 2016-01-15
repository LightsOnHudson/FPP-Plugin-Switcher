<?php
function hex_dump($data, $newline="\n")
{
  static $from = '';
  static $to = '';

  static $width = 16; # number of bytes per line

  static $pad = '.'; # padding for non-visible characters

  if ($from==='')
  {
    for ($i=0; $i<=0xFF; $i++)
    {
      $from .= chr($i);
      $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
    }
  }

  $hex = str_split(bin2hex($data), $width*2);
  $chars = str_split(strtr($data, $from, $to), $width);

$HEX_OUT ="";
  $offset = 0;
  foreach ($hex as $i => $line)
  {
    $HEX_OUT.= sprintf('%6X',$offset).' : '.implode(' ', str_split($line,2)) . ' [' . $chars[$i] . ']';
    $offset += $width;
  }
return $HEX_OUT;
}

function decode_code($code)
{
    return preg_replace_callback('@\\\(x)?([0-9a-f]{2,3})@',
        function ($m) {
            if ($m[1]) {
                $hex = substr($m[2], 0, 2);
                $unhex = chr(hexdec($hex));
		echo "UNHEX: ".$unhex;
                if (strlen($m[2]) > 2) {
                    $unhex .= substr($m[2], 2);
                }
                return $unhex;
            } else {
                return chr(octdec($m[2]));
            }
        }, $code);
}

//print the different projectors for plugin setup
function printSwitcherSelect() {
	
	global $SWITCHERS,$SWITCHER_READ;
	
	//print_r($SWITCHERS);
	
	echo "<select name=\"SWITCHER\"> \n";
	
	foreach ($SWITCHERS as $switcher) {
		
		if($switcher['NAME'] == $SWITCHER_READ) {
			echo "<option selected value=\"".$switcher['NAME']."\">".$switcher['NAME']."</option> \n";
		} else {
			echo "<option value=\"".$switcher['NAME']."\">".$switcher['NAME']."</option> \n";
		}
	}
	
	
	
	echo "</select> \n";
	
}


function createProjectorEventFiles() {
	
	global $eventDirectory,$SWITCHERS,$SWITCHER_READ,$pluginDirectory,$pluginName,$scriptDirectory,$DEVICE_CONNECTION_TYPE,$DEVICE;
	
	
		
	//echo "next event file name available: ".$nextEventFilename."\n";

	$SWITCHER_FOUND=false;
	
	for($projectorIndex=0;$projectorIndex<=count($SWITCHERS)-1;$projectorIndex++) {
	
		if($SWITCHERS[$projectorIndex]['NAME'] == $SWITCHER_READ) {
		
		//	echo "CMD: ".$cmd."\n";
		//iterate through the various keys and make a file for them
			//	print_r($SWITCHERS[$projectorIndex]);
		//	echo "Processing files for projector name : ".$SWITCHER_READ."<br/> \n";
			while (list($key, $val) = each($SWITCHERS[$projectorIndex])) {
			//	echo "key: ".$key." -- value: ".$val."\n";

				if($key != "NAME" && $key != "BAUD_RATE" && $key != "CHAR_BITS" && $key != "PARITY" && $key != "STOP_BITS" && $key != "VALID_STATUS_0" && $key != "VALID_STATUS_1" && $key != "VALID_STATUS_2")
				{
					
					//check to see that the file doesnt already exist - do a grep and return contents
					$EVENT_CHECK = checkEventFilesForKey("SWITCHER-".$key);
					if(!$EVENT_CHECK)
					{
					
						$nextEventFilename = getNextEventFilename();
						$MAJOR=substr($nextEventFilename,0,2);
						$MINOR=substr($nextEventFilename,3,2);
						$eventData  ="";
						$eventData  = "majorID=".(int)$MAJOR."\n";
						$eventData .= "minorID=".(int)$MINOR."\n";
						$eventData .= "name='SWITCHER-".$key."'\n";
						$eventData .= "effect=''\n";
						$eventData .= "startChannel=\n";
						$eventData .= "script='SWITCHER-".$key.".sh'\n";
						
					//	echo "eventData: ".$eventData."<br/>\n";
						file_put_contents($eventDirectory."/".$nextEventFilename, $eventData);
						
						$scriptCMD = $pluginDirectory."/".$pluginName."/"."switcher.php -d".$DEVICE_CONNECTION_TYPE." -s".$DEVICE." -c".$key;
						createScriptFile("SWITCHER-".$key.".sh",$scriptCMD);
					}
				}
				
				//echo "$key => $val\n";
			}
		}
	}
	
	
	
}


function logEntry($data) {

	global $logFile,$myPid,$callBackPid;
	
	if($callBackPid != "") {
		$data = $_SERVER['PHP_SELF']." : [".$callBackPid.":".$myPid."] ".$data;
	} else { 
	
		$data = $_SERVER['PHP_SELF']." : [".$myPid."] ".$data;
	}
	$logWrite= fopen($logFile, "a") or die("Unable to open file!");
	fwrite($logWrite, date('Y-m-d h:i:s A',time()).": ".$data."\n");
	fclose($logWrite);
}


function escapeshellarg_special($file) {
	return "'" . str_replace("'", "'\"'\"'", $file) . "'";
}


//function send the message

function sendCommand($switcherCommand) {

	global $pluginName,$myPid,$pluginDirectory,$DEVICE,$DEVICE_CONNECTION_TYPE,$IP,$PORT;
	
	//$DEVICE = ReadSettingFromFile("DEVICE",$pluginName);
	//$DEVICE_CONNECTION_TYPE = ReadSettingFromFile("DEVICE_CONNECTION_TYPE",$pluginName);
	//$IP = ReadSettingFromFile("IP",$pluginName);
	//$PORT = ReadSettingFromFile("PORT",$pluginName);
	
	//$ENABLED = ReadSettingFromFile("ENABLED",$pluginName);

//	logEntry("reading config file");
//	logEntry(" DEVICE: ".$DEVICE." DEVICE_CONNECTION_TYPE: ".$DEVICE_CONNECTION_TYPE." IP: ".$IP. " PORT: ".$PORT);

	//logEntry("INSIDE SEND");
	//# Send line to  Projector
	$cmd = $pluginDirectory."/".$pluginName."/proj.php ";

	$cmd .= "-d".$DEVICE_CONNECTION_TYPE;


	switch ($DEVICE_CONNECTION_TYPE) {

		case "SERIAL":

			$SERIALCMD = " -s".$DEVICE." -c".$switcherCommand;
			$cmd .= $SERIALCMD;
				
			break;
				
		case "IP":
			$IPCMD = " -h".$IP. " -c".$switcherCommand;
			$cmd .= $IPCMD;
			break;
				
	}

	$cmd .= " -z".$myPid;
	
	logEntry("COMMAND: ".$cmd);
	system($cmd,$output);

	//system($cmd."\"".$line."\" ".$DEVICE,$output);
}

function processSequenceName($sequenceName) {
	
	global $projectorONSequence, $projectorOFFSequence,$projectorVIDEOSequence;
	
	logEntry("Sequence name: ".$sequenceName);

	$sequenceName = strtoupper($sequenceName);

	switch ($sequenceName) {

		
				
		default:
			logEntry("We do not support sequence name: ".$sequenceName." at this time");
				
			exit(0);
				
	}
	


}
function processCallback($argv) {

	global $DEBUG,$pluginName;
	
	
	if($DEBUG)
		print_r($argv);
	//argv0 = program
	
	//argv2 should equal our registration // need to process all the rgistrations we may have, array??
	//argv3 should be --data
	//argv4 should be json data
	
	$registrationType = $argv[2];
	$data =  $argv[4];
	
	logEntry("PROCESSING CALLBACK");
	$clearMessage=FALSE;
	
	switch ($registrationType)
	{
		case "media":
			if($argv[3] == "--data")
			{
				$data=trim($data);
				logEntry("DATA: ".$data);
				$obj = json_decode($data);
	
				$type = $obj->{'type'};
	
				switch ($type) {
						
					case "sequence":
	
						//$sequenceName = ;
						processSequenceName($obj->{'Sequence'});
							
						break;
					case "media":
							
						logEntry("We do not understand type media at this time");
							
						exit(0);
	
						break;
	
					default:
						logEntry("We do not understand: type: ".$obj->{'type'}. " at this time");
						exit(0);
						break;
	
				}
	
	
			}
	
			break;
			exit(0);
				
		default:
			exit(0);
	
	}
	


}
?>
