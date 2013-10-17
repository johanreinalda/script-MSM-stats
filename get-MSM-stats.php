#!/usr/bin/php
<?php
// =====================================================================
// This file is part of get-MSM-stats.php
//
// This script is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this script.  If not, see <http://www.gnu.org/licenses/>.
// =====================================================================

define('VERSION','2013101700');

/**
 * get MSM wireless association statistics
 *
 * @package    get-MSM-stats
 * @copyright  2013 onwards, Johan Reinalda  < johan at reinalda dot net >
 * @author     2013 Johan Reinalda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @url        https://github.com/johanreinalda
 *
 */
// this script will create a CSV file with information about the associations of all MSM radios
// managed by an HP MSM wireless controller.
// MSM controller specific information is read from an include file "MSM-config.inc.php"
// here you can also define the output file name and location
// this CSV file can be used to further manipulate data with a spreadsheet application, etc.
//
// Note: this script is written and tested in a Linux environment,
// but should work fine with any OS and PHP >= v5.3

// if set to true, lots of extra debugging output to stdout (standard out)
$DEBUG = false;

//==================//
// load config variables //
//==================//

require_once 'MSM-config.inc.php';


//=== Current output fields are:
//
// Date, AP_Name, Radio1_5GHz, Radio2_2.4GHz, AP_Total, Client-a, Client-b, Client-g, Client-bg, Client-n
//
// NOTE: if this format changes, modify the function writeHeader() at the end !!!
//
//====================================================================
// START OF CODE
//====================================================================

//MSM Radio names:
//snmpwalk -v 1 -c IT6999NP 10.254.0.70 SNMPv2-SMI::enterprises.8744.5.23.1.2.1.1.6
//SNMPv2-SMI::enterprises.8744.5.23.1.2.1.1.6
$RadioNameOID = '1.3.6.1.4.1.8744.5.23.1.2.1.1.6';

//MSM Radio associations:
//snmpwalk -v 1 -c IT6999NP 10.254.0.70 SNMPv2-SMI::enterprises.8744.5.25.1.2.1.1.9
// SNMPv2-SMI::enterprises.8744.5.25.1.2.1.1.9
$AssociationsOID = '1.3.6.1.4.1.8744.5.25.1.2.1.1.9';

//Client radio types, ie a,b,g,n
//snmpwalk -v 1 -c IT6999NP 10.254.0.70 SNMPv2-SMI::enterprises.8744.5.25.1.7.1.1.15
$ClientRadioTypeOID = '1.3.6.1.4.1.8744.5.25.1.7.1.1.15';
define('MAX_RADIO_TYPES',5);
$RadioTypes = Array(
'1' => 'a',
'2' => 'b',
'3' => 'g',
'4' => 'bg',
'5' => 'n'
);


//======================================================================
//open log file
//if output file does not exist yet, we need to write header as first line!
$addHeader = !file_exists($file);

if(($fp = fopen($file,'a')) === FALSE) {
	print "CANNOT open file $file\n";
	exit;
}

if($addHeader) writeHeader($fp);

//global total counts
$radioTotals = Array();
$radioTotals[1] = 0; //radio 1 has 0 clients
$radioTotals[2] = 0;
//set all known client radio types to 0
$clientTypeTotals = Array();
for( $i=1; $i < MAX_RADIO_TYPES+1; $i++ ) { $clientTypeTotals[$i] = 0; }

//get the date stamp, in Excel readable format, eg. "03/01/12 08:15:12"
$date = date('Y/m/d H:i:s');

//get all attached MSM radios, with their index and name 
$names = snmprealwalk($MSMController,$Community,$RadioNameOID);
//print_r($names);

//loop through all access points and get names
//we could also loop through ClientRadioTypeOID,
//but then we do not get radios with 0 associations
foreach ($names as $oid => $value) {
	dprint("\n===============================================\n");
	dprint("$oid => $value\n");

    
	//parse proper radio index, removing OID path
	$ind = array();
	if(preg_match('/SNMPv2-SMI::enterprises.8744.5.23.1.2.1.1.6.(\d+)/', $oid, $ind)) {
		$radioIndex = $ind[1];
        //parse proper radio name, removing the snmp entity type
        $radioName = $value;
        $n = array();
        if(preg_match('/STRING\: (.*)/', $value, $n)) {
            $radioName = $n[1];
        }
        dprint("\nRadio ID $radioIndex => $radioName\n");
        
        //find all associations for this radio
        //we could also loop through ClientRadioTypeOID,
        //but then we do not get radios with 0 associations
        $clients = snmprealwalk($MSMController,$Community,$ClientRadioTypeOID.'.'.$radioIndex);
        dprint(print_r($clients,true));

        //set counters per radio to 0
        $radioClients[1] = 0; //radio 1 has 0 clients
        $radioClients[2] = 0;
        //set all known client radio types to 0
        $clientTypes = Array();
        for( $i=1; $i < MAX_RADIO_TYPES+1; $i++ ) { $clientTypes[$i] = 0; }
        
        foreach($clients as $clientOID => $clientValue) {
            //remove the snmp entity type from $count
            $c = array();
            if(preg_match('/INTEGER\: (\d+)/',$clientValue,$c)) {
                $clientType = $c[1];
            }

            $cdata = array();
            //make sure we are looking at the proper association data
            if(preg_match('/SNMPv2-SMI::enterprises.8744.5.25.1.7.1.1.15.(\d+).(\d+).(\d+)/', $clientOID, $cdata)) {
                $radioIndex = $cdata[1];
                $radioType = $cdata[2];
                $clientIndex = $cdata[3];
                dprint("Clients on radio = $radioIndex, $radioType, $clientIndex => clientType $clientType\n");
                //adjust totals for this radio and this client
                $radioClients[$radioType]++;
                $clientTypes[$clientType]++;
                //adjust the global totals as well
                $radioTotals[$radioType]++;
                $clientTypeTotals[$clientType]++;
                
            } else {
                //no associations found for this AP
                dprint("NO clients on this AP!\n");
            }
        }

        //write stats for this access point
        $APTotal = $radioClients[1] + $radioClients[2];
        dprint("$radioName  $radioClients[1]  $radioClients[2]\n");
        //need to add client types to this still
        fwrite($fp,"\"$date\",$radioName,\"$radioClients[1]\",\"$radioClients[2]\",\"$APTotal\"");
        //write client radio types info
        for( $i=1; $i < MAX_RADIO_TYPES+1; $i++ ) {
            fwrite($fp,',"' . $clientTypes[$i] . '"');
        }
        //finish line
        fwrite($fp,"\n");
        
    } else {
		print "Error: Radio index not found!\n";
		exit;
	}
}

// write totals to all radios
$Total = $radioTotals[1] + $radioTotals[2];
fwrite($fp,"\"$date\",\"TOTAL\",\"$radioTotals[1]\",\"$radioTotals[2]\",\"$Total\"");
//write total client radio types info
for( $i=1; $i < MAX_RADIO_TYPES+1; $i++ ) {
    fwrite($fp,',"' . $clientTypeTotals[$i] . '"');
}
//finish line
fwrite($fp,"\n");

fclose($fp);
exit;


//========================================

function writeHeader($fp) {
    // Date, AP_Name, Radio1_5GHz, Radio2_2.4GHz, AP_Total, Client-a, Client-b, Client-g, Client-bg, Client-n
    fwrite($fp,'"Date","AP_Name","Radio1(5GHz)","Radio2(2.4GHz)","AP_Total","Client-a","Client-b","Client-g","Client-bg","Client-n"' . "\n");
}

function dprint($string) {
global $DEBUG;
	if($DEBUG) print($string);
}

