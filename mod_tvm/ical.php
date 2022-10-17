<?php 
	
	define( '_JEXEC', 1 );
	define( 'DS', DIRECTORY_SEPARATOR );
	define( 'JPATH_BASE', $_SERVER[ 'DOCUMENT_ROOT' ] );

	require_once( JPATH_BASE . DS . 'includes' . DS . 'defines.php' );
	require_once( JPATH_BASE . DS . 'includes' . DS . 'framework.php' );
//	require_once( JPATH_BASE . DS . 'libraries' . DS . 'joomla' . DS . 'factory.php' );	
	require_once( JPATH_BASE . DS . 'libraries' . DS . 'import.php' ); // framework
	require_once( JPATH_BASE . DS . 'configuration.php' ); // config file
	
	date_default_timezone_set("Europe/Berlin"); 
	
	$mainframe =& JFactory::getApplication('site');
	$mainframe->initialise();

	$eventID = $_GET["event_id"];

	// Obtain a database connection
	$db = JFactory::getDbo();
	// Retrieve the shout
	/*$query = $db->getQuery(true);
	$query->select($db->quoteName('*'));
	$query->from($db->quoteName('#__tvm_events'));
	$query->where($db->quoteName('id').' = '.$db->quote($eventID));
	$db->setQuery($query);*/
	$query = $db->getQuery(true);
	$query->select('*')->from('#__tvm_events')->where('id = '.$db->quote($eventID));
	$db->setQuery($query);
	
	// Load the row.
	$event = $db->loadObject();
	
	$returnValue = "";
	
	$endTime = strtotime($event->date .' '.$event->starttime) + $event->duration*60;

	if($event->published != '0') {
		// 1. Set the correct headers for this file
		header("Content-type: text/calendar; charset=utf-8");
		header("Content-Disposition: attachment; filename=tvm_event_".$event->title.".ics");
		$returnValue .= "BEGIN:VCALENDAR\r\n";
		$returnValue .= "VERSION:2.0\r\n";
		$returnValue .= "PRODID:-//hacksw/handcal//NONSGML v1.0//EN\r\n";
		$returnValue .= "CALSCALE:GREGORIAN\r\n";
		$returnValue .= "BEGIN:VEVENT\r\n";
		$returnValue .= "DTEND:".date('Ymd\THis\Z', $endTime)."\r\n";
		$returnValue .= "UID:".uniqid()."\r\n";
		$returnValue .= "DTSTAMP:".date('Ymd\THis\Z', time())."\r\n";
		$returnValue .= "LOCATION:".addslashes($event->location)."\r\n";
		$returnValue .= "DESCRIPTION:".addslashes($event->event_comment)."\r\n";
		$returnValue .= "URL;VALUE=URI:".addslashes("")."\r\n";
		$returnValue .= "SUMMARY:".addslashes($event->title)."\r\n";
		$returnValue .= "DTSTART:".date('Ymd\THis\Z', strtotime($event->date .' '.$event->starttime))."\r\n";
		$returnValue .= "END:VEVENT\r\n";
		$returnValue .= "END:VCALENDAR\r\n";
	}		
	echo $returnValue;
?>
