<?php

/******************************************************************
 * cdav is a Dolibarr module
 * It allows caldav and carddav clients to sync with Dolibarr
 * calendars and contacts.
 *
 * cdav is distributed under GNU/GPLv3 license
 * (see COPYING file)
 *
 * cdav uses Sabre/dav library http://sabre.io/dav/
 * Sabre/dav is distributed under use the three-clause BSD-license
 *
 * Author : Befox SARL http://www.befox.fr/
 *
 ******************************************************************/

define('NOTOKENRENEWAL',1); 								// Disables token renewal
if (! defined('NOLOGIN')) define('NOLOGIN','1');
if (! defined('NOCSRFCHECK')) define('NOCSRFCHECK','1');	// We accept to go on this page from external web site.
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX','1');
function llxHeader() { }
function llxFooter() { }

function base64url_decode($data) {
	return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

if(is_file('../main.inc.php'))
	$dir = '../';
elseif(is_file('../../../main.inc.php'))
	$dir = '../../../';
else
	$dir = '../../';

require $dir.'main.inc.php';	// Load $user and permissions

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load traductions files requiredby by page
$langs->load("cdav");

define ('CDAV_URI_KEY', $conf->global->CDAV_URI_KEY);

//parse Token
$arrTmp = explode('+ø+', mcrypt_decrypt(MCRYPT_BLOWFISH, CDAV_URI_KEY, base64url_decode(GETPOST('token')), 'ecb'));

if (! isset($arrTmp[1]) || ! in_array(trim($arrTmp[1]), array('nolabel', 'full')))
{
	echo 'Unauthorized Access !';
	exit;
}

$id 	= trim($arrTmp[0]);
$type 	= trim($arrTmp[1]);

header('Content-type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename=Calendar-'.$id.'-'.$type.'.ics');

//fake user having right on this calendar
$user = new stdClass();

$user->rights = new stdClass();
$user->rights->agenda = new stdClass();
$user->rights->agenda->myactions = new stdClass();
$user->rights->agenda->allactions = new stdClass();
$user->rights->societe = new stdClass();
$user->rights->societe->client = new stdClass();

$user->id = $id;
$user->rights->agenda->myactions->read = true;
$user->rights->agenda->allactions->read = true;
$user->rights->societe->client->voir = false;

//Get all event
require_once './lib/cdav.lib.php';
$cdavLib = new CdavLib($user, $db, $langs);

//Format them
$arrEvents = $cdavLib->getFullCalendarObjects($id, true);

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "METHOD:PUBLISH\r\n";
echo "PRODID:-//Dolibarr CDav//FR\r\n";

echo "X-PUBLISHED-TTL:PT5M\r\n";

echo "BEGIN:VTIMEZONE\r\n";
echo "TZID:Europe/Berlin\r\n";
echo "BEGIN:DAYLIGHT\r\n";
echo "TZOFFSETFROM:+0100\r\n";
echo "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\n";
echo "DTSTART:19810329T020000\r\n";
echo "TZNAME:GMT+2\r\n";
echo "TZOFFSETTO:+0200\r\n";
echo "END:DAYLIGHT\r\n";
echo "BEGIN:STANDARD\r\n";
echo "TZOFFSETFROM:+0200\r\n";
echo "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU\r\n";
echo "DTSTART:19961027T030000\r\n";
echo "TZNAME:GMT+1\r\n";
echo "TZOFFSETTO:+0100\r\n";
echo "END:STANDARD\r\n";
echo "END:VTIMEZONE\r\n";

echo "BEGIN:VTIMEZONE\r\n";
echo "TZID:Europe/Vienna\r\n";
echo "BEGIN:DAYLIGHT\r\n";
echo "TZOFFSETFROM:+0100\r\n";
echo "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\n";
echo "DTSTART:19810329T020000\r\n";
echo "TZNAME:GMT+2\r\n";
echo "TZOFFSETTO:+0200\r\n";
echo "END:DAYLIGHT\r\n";
echo "BEGIN:STANDARD\r\n";
echo "TZOFFSETFROM:+0200\r\n";
echo "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU\r\n";
echo "DTSTART:19961027T030000\r\n";
echo "TZNAME:GMT+1\r\n";
echo "TZOFFSETTO:+0100\r\n";
echo "END:STANDARD\r\n";
echo "END:VTIMEZONE\r\n";

$anz = 0;


foreach($arrEvents as $event)
{
	if ($type == 'nolabel')
	{
		//Remove SUMMARY / DESCRIPTION / LOCATION
		$event['calendardata'] = preg_replace('#SUMMARY:.*[^\n]#', 'SUMMARY:'.$langs->trans('Busy'), $event['calendardata']);//FIXME translate busy !!
		$event['calendardata'] = preg_replace('#DESCRIPTION:.*[^\n]#', 'DESCRIPTION:.', $event['calendardata']);
		$event['calendardata'] = preg_replace('#LOCATION:.*[^\n]#', 'LOCATION:', $event['calendardata']);

		$event ['calendardata'] = preg_replace('~\R~u', "\r\n", $event ['calendardata']);

		echo $event['calendardata'];

	}
	else
	{
		$event ['calendardata'] = preg_replace('~\R~u', "\r\n", $event ['calendardata']);
		echo $event['calendardata'];
	}
	$anz++;
}

echo "END:VCALENDAR\r\n";

loginfo ("User: " . $id . ", Anz.Events: " . $anz);

function loginfo ($text)
{
	$myfile = fopen("/var/lib/dolibarr/documents/cdav.log", "a+") or die("Unable to open file!");

	fwrite($myfile,date("Y-m-d H:i:s",time()).": " . $text . "\n");
	fclose($myfile);
}
