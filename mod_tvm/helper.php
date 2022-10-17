<?php
/**
 * Helper class for Hello World! module
 * 
 * @package    Joomla.Tutorials
 * @subpackage Modules
 * @link http://docs.joomla.org/J3.x:Creating_a_simple_module/Developing_a_Basic_Module
 * @license        GNU/GPL, see LICENSE.php
 * mod_helloworld is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

class ModTvmHelper
{
    /**
     * Retrieves the hello message
     *
     * @param   array  $params An object containing the module parameters
     *
     * @access public
     */    
    public static function getTvm($params)
    {
        return 'Hello, World!';
    }
	/*
	public static function getIcalAjax() {
		date_default_timezone_set("Europe/Berlin"); 
		$input = JFactory::getApplication()->input;		
		$formData  = $input->get('data', array(), 'array');

		$eventID = $formData['event_id'];
	

		// Obtain a database connection
		$db = JFactory::getDbo();
		// Retrieve the shout

		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__tvm_events');
		$query->where('id = '.$eventID);
		$db->setQuery($query);
		
		// Load the row.
		$event = $db->loadObject();
		
		$returnValue = "";
		
		$endTime = strtotime($event->date .' '.$event->starttime) + $event->duration*60;

		if($event->published != '0') {
			// 1. Set the correct headers for this file
			header("Content-type: text/calendar; charset=utf-8");
			header("Content-Disposition: attachment; filename=tvm_event.ics");
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
		return $returnValue;
	}
	*/
	public static function getAjax()
	{
		// Obtain a database connection
		$db = JFactory::getDbo();
		// Retrieve the shout
		$query = $db->getQuery(true);
		
		$input = JFactory::getApplication()->input;		
		$formData  = $input->get('data', array(), 'array');
		
		$id = $formData['id'];
		$sel = $formData['selection'];		
		$user_id = $formData['user_id'];
		$comment = $formData['comment'];
		
		$state = 0;
		
		if(strcmp($sel,'ja') == 0) {
			$state = 1;
		} else if(strcmp($sel,'nein') == 0) {
			$state = 3;
		} else if(strcmp($sel,'vlt') == 0) {
			$state = 2;
		}
		// first check, if user is already registered
		$query->select($db->quoteName('id'));
	    $query->from($db->quoteName('#__tvm_entry'));
		$query->where($db->quoteName('event_id').' = '.$db->quote($id).' AND '.$db->quoteName('user_id').' = '.$db->quote($user_id));
		$db->setQuery($query);
		//Load the row.
		 $DBresult = $db->loadResult();
		 $ret = "";
		 if($DBresult > 0){
			// gefunden --> update

			$ret = 'gefunden';
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			// Fields to update.
			$fields = array(
				$db->quoteName('state') . ' = ' . $db->quote($state),
				$db->quoteName('comment') . ' = ' . $db->quote($comment),
				$db->quoteName('updated') . ' = NOW()',
				$db->quoteName('updated_by') . ' = '. $db->quote($user_id),
				$db->quoteName('acknowledged') . ' = 0',
			);
			 
			// Conditions for which records should be updated.
			$conditions = array(
				$db->quoteName('user_id') . ' = '.$db->quote($user_id), 
				$db->quoteName('event_id') . ' = ' . $db->quote($id)
			);
			$query->update($db->quoteName('#__tvm_entry'))->set($fields)->where($conditions);
			$db->setQuery($query);
			$entryCount = $db->execute();
		 } else {
			// nicht gefunden --> neu anlegen
			$ret='nicht gefunden';
			// Insert columns.
			$columns = array('user_id', 'event_id', 'state', 'acknowledged', 'comment','published');
			// Insert values.
			$values = array( $db->quote($user_id),  $db->quote($id),  $db->quote($state), 0, $db->quote($comment), 1);
			// Retrieve the shout
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->insert($db->quoteName('#__tvm_entry'));
			$query->columns($db->quoteName($columns));
			$query->values(implode(',', $values));
			 
			// Set the query using our newly populated query object and execute it.
			$db->setQuery($query);
			$entryCount = $db->execute();
		 }
		
		return $state;
		
	}
	
	public static function setAckAjax()
	{
	
		$input = JFactory::getApplication()->input;		
		$formData  = $input->get('data', array(), 'array');
		
		$event_id = $formData['event_id'];
		$user_id = $formData['user_id'];		
		$ack = $formData['ack'];
		$editor_id = $formData['editor_id'];
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		// Fields to update.
		$fields = array(
			$db->quoteName('acknowledged') . ' = ' . $db->quote($ack),
			$db->quoteName('state') . ' = 1',
			$db->quoteName('updated') . ' = NOW()',
			$db->quoteName('updated_by') . ' = '. $db->quote($editor_id),
		);
		 
		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('user_id') . ' = '.$db->quote($user_id), 
			$db->quoteName('event_id') . ' = ' . $db->quote($event_id)
		);
		$query->update($db->quoteName('#__tvm_entry'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$entryCount = $db->execute();		
		return $entryCount;
		
	}
	
	
	public static function setCommentAjax()
	{
	
		$input = JFactory::getApplication()->input;		
		$formData  = $input->get('data', array(), 'array');
		
		//print_r($formData);
		
		$event_id = $formData['event_id'];
		$user_id = $formData['user_id'];		
		$comment = htmlspecialchars($formData['comment']);
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		// Fields to update.
		$fields = array(
			$db->quoteName('comment') . ' = ' . $db->quote($comment),
			$db->quoteName('updated') . ' = NOW()',
			$db->quoteName('updated_by') . ' = '. $db->quote($user_id),
		);
		 
		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('user_id') . ' = '.$db->quote($user_id), 
			$db->quoteName('event_id') . ' = ' . $db->quote($event_id)
		);
		$query->update($db->quoteName('#__tvm_entry'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$entryCount = $db->execute();
		
		return $entryCount;
		
	}
	
	public static function refuseEventAjax(){ 
		$input = JFactory::getApplication()->input;		
		$formData  = $input->get('data', array(), 'array');
		
		$event_id = $formData['event_id'];
		$users = ModTvmHelper::getTvmUserByEvent($event_id);
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		// Fields to update.
		$fields = array(
			$db->quoteName('state') . ' = ' . $db->quote('4'),
			$db->quoteName('updated') . ' = NOW()',
		);
		 
		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('event_id') . ' = ' . $db->quote($event_id)
		);
		$query->update($db->quoteName('#__tvm_entry'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$entryCount = $db->execute();
		
		// send mail to userv
		// first get event data
		// Obtain a database connection
		$db = JFactory::getDbo();
		// Retrieve the shout
		$query = $db->getQuery(true);
 
		$query->select($db->quoteName(array('date','starttime','title')));
        $query->from($db->quoteName('#__tvm_events'));
		$query->where($db->quoteName('id').' = '.$db->quote($event_id));
		$db->setQuery($query);
		
		// Load the row.
		$resultEvent = $db->loadAssoc();
		
		$recipient = array();
		foreach($users as $user) {
			if($user->state < 3) { // only yes and maybe
				array_push($recipient, JFactory::getUser($user->user_id)->email);
			}
		}				
		if(count($recipient) > 0) {
			$config = JFactory::getConfig();  
			$sender = array( 
				$config->get( 'mailfrom' ),
				$config->get( 'fromname' ) 
			);
			$mailer = JFactory::getMailer();
			$mailer->setSender($sender);
			$mailer->addRecipient($recipient);
			$mailer->setSubject('Trainingsausfall '.$resultEvent['title'] . ' am '.$resultEvent['date'].' um '.$resultEvent['starttime'].' Uhr');
			$body = "Hallo zusammen\n\r";
			$body .= "Es gibt ein Update für das Training ".$resultEvent['title'] . " am ".$resultEvent['date']." um ".$resultEvent['starttime']." Uhr.\n\r";
			$body .= "Inhalt: Das Training fällt aus.\n\r";
			$mailer->setBody($body);
			$send = $mailer->Send();
			if ( $send !== true ) {
				echo 'Error sending email: ' . $send->__toString();
			} else {
				echo 'Mail sent';
			}
		}
		// set event to closed in DB
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		// Fields to update.
		$fields = array(
			$db->quoteName('closed') . ' = ' . $db->quote(1),
		);
		 
		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('id') . ' = ' . $db->quote($event_id)
		);
		$query->update($db->quoteName('#__tvm_events'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$entryCount = $db->execute();
		
		return true;
	}
	
	public static function sendMailToUsersAjax() {
		$input = JFactory::getApplication()->input;		
		$formData  = $input->get('data', array(), 'array');
		
		$event_id = $formData['event_id'];
		$mail_msg = $formData['mail_message'];
		
		// send mail to userv
		// first get event data
		// Obtain a database connection
		$db = JFactory::getDbo();
		// Retrieve the shout
		$query = $db->getQuery(true);
 
		$query->select($db->quoteName(array('date','starttime','title')));
        $query->from($db->quoteName('#__tvm_events'));
		$query->where($db->quoteName('id').' = '.$db->quote($event_id));
		$db->setQuery($query);
		
		// Load the row.
		$resultEvent = $db->loadAssoc();
		
		$users = ModTvmHelper::getTvmUserByEvent($event_id);
		$recipient = array();

		foreach($users as $user) {
			array_push($recipient, JFactory::getUser($user->user_id)->email);
		}
						
		if(count($recipient) > 0) {
			$config = JFactory::getConfig();  
			$sender = array( 
				$config->get( 'mailfrom' ),
				$config->get( 'fromname' ) 
			);
			$mailer = JFactory::getMailer();
			$mailer->setSender($sender);
			$mailer->addRecipient($recipient);
			$mailer->setSubject('Trainingsinfo '.$resultEvent['title'] . ' am '.$resultEvent['date'].' um '.$resultEvent['starttime'].' Uhr');
			$body = "Hallo zusammen\n\r";
			$body .= "Es gibt ein Update für das Training ".$resultEvent['title'] . " am ".$resultEvent['date']." um ".$resultEvent['starttime']." Uhr.\n\r";
			$body .= "Inhalt: ".$mail_msg."\n\r";
			$mailer->setBody($body);
			$send = $mailer->Send();
			if ( $send !== true ) {
				echo 'Error sending email: ' . $send->__toString();
				return false;
			} else {
				echo 'Mail sent';
			}
		}	
		return true;
	}
	
	public static function removeUserFromEventAjax(){ 
		$input = JFactory::getApplication()->input;		
		$formData  = $input->get('data', array(), 'array');
		
		$event_id = $formData['event_id'];
		$user_id = $formData['user_id'];		
		$editor_id = $formData['editor_id'];
		$mail_msg = $formData['mail_message'];
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		// Fields to update.
		$fields = array(
			$db->quoteName('state') . ' = ' . $db->quote('4'),
			$db->quoteName('updated') . ' = NOW()',
			$db->quoteName('updated_by') . ' = '. $db->quote($user_id),
		);
		 
		// Conditions for which records should be updated.
		$conditions = array(
			$db->quoteName('user_id') . ' = '.$db->quote($user_id), 
			$db->quoteName('event_id') . ' = ' . $db->quote($event_id)
		);
		$query->update($db->quoteName('#__tvm_entry'))->set($fields)->where($conditions);
		$db->setQuery($query);
		$entryCount = $db->execute();
		
		// send mail to user
		// first get event data
		// Obtain a database connection
		$db = JFactory::getDbo();
		// Retrieve the shout
		$query = $db->getQuery(true);
 
		$query->select($db->quoteName(array('b.username','a.date','a.starttime','a.title','a.location')));
        $query->from($db->quoteName('#__tvm_events','a'));
		$query->join('INNER',$db->quoteName('#__users','b') . ' ON (' . $db->quoteName('a.user_id') . ' = ' . $db->quoteName('b.id').')');
		$query->where($db->quoteName('a.id').' = '.$db->quote($event_id));
		$db->setQuery($query);
		
		// Load the row.
		$resultEvent = $db->loadAssoc();
				
		$userTo = JFactory::getUser($user_id);
		$config = JFactory::getConfig();  
		$sender = array( 
			$config->get( 'mailfrom' ),
			$config->get( 'fromname' ) 
		);
		$mailer = JFactory::getMailer();
		$mailer->setSender($sender);
		$recipient = $userTo->email;
		$mailer->addRecipient($recipient);
		$mailer->setSubject('Trainingsupdate '.$resultEvent['title'] . ' am '.$resultEvent['date'].' um '.$resultEvent['starttime'].' Uhr');
		$body = "Hallo ".$userTo->username."\n\r";
		$body .= "Es gibt ein Update für das Training ".$resultEvent['title'] . " am ".$resultEvent['date']." um ".$resultEvent['starttime']." Uhr.\n\r";
		$body .= "Inhalt: " .$mail_msg."\n\r";
		$mailer->setBody($body);
		$send = $mailer->Send();
		if ( $send !== true ) {
			echo 'Error sending email: ' . $send->__toString();
		} else {
			echo 'Mail sent';
		}
		return $mail_msg;
		
	}
		
	public static function checkAndUpdatePeriodicEvents(){
		
		date_default_timezone_set("Europe/Berlin"); 
		// Obtain a database connection
		$db = JFactory::getDbo();
		// Retrieve the shout
		$query = $db->getQuery(true);
 
		$query->select($db->quoteName(array('id', 'date', 'periodic','periodic_value', 'starttime','duration')));
        $query->from($db->quoteName('#__tvm_events'));		
		$query->where($db->quoteName('periodic').' != '.$db->quote('none'));
		$query->order($db->quoteName('date') . ' ASC');
		$db->setQuery($query);
		$result = $db->loadObjectList();
		
		foreach($result as $row) {
			switch($row->periodic) {
				case 'weekly':
					//var_dump(date("Y-m-d"));
					//var_dump(date("Y-m-d",strtotime($row->date)));
					// try to find difference in days
					//if(date("Y-m-d") > date("Y-m-d",strtotime($row->date))) {
					if(date("Y-m-d") > $row->date) {
						// Entry is older than today, we should update
						$newDate = date("Y-m-d",strtotime($row->date)+(60*60*24*7));
						modTvmHelper::deleteEntriesFromEvent($row->id);
						modTvmHelper::updateEventDate($row->id, $newDate, true);
					}
					
					//$eventClock = explode(":",$row->starttime);
					$endTime = strtotime($row->date .' '.$row->starttime) + $row->duration*60;
					//var_dump($endTime);
					//var_dump(time());
					//print_r(date("H").' ');
					//print_r(date("i").' ');
					//print_r(date("s").' ');
					//die();
					//date("H:i:s")
					if(time() >= $endTime ) {
						//die();
						// Entry is older than today, we should update
						$newDate = date("Y-m-d",strtotime($row->date)+(60*60*24*7));
						modTvmHelper::deleteEntriesFromEvent($row->id);
						modTvmHelper::updateEventDate($row->id, $newDate, true);
					}
					break;
			}
		}
	}
	
	private static function updateEventDate($id = 0, $newDate, $isPeriodic = false ){
		// Obtain a database connection
		$db = JFactory::getDbo();
		// Retrieve the shout
		$query = $db->getQuery(true);
		$query->update($db->quoteName('#__tvm_events'));
		if($isPeriodic) {
			$query->set(
			array(
				$db->quoteName('date'). ' = '. $db->quote($newDate),
				$db->quoteName('closed'). ' = '. $db->quote('0'))
			);
		} else {
			$query->set($db->quoteName('date'). ' = '. $db->quote($newDate));
		}
		$query->set($db->quoteName('date'). ' = '. $db->quote($newDate));
		
		$query->where($db->quoteName('id').' = '.$db->quote($id));
		$db->setQuery($query);
		$result = $db->execute();
		
	}
	
	private static function deleteEntriesFromEvent($id) {
		// Obtain a database connection
		$db = JFactory::getDbo();
		// Retrieve the shout
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__tvm_entry'));
		$query->where($db->quoteName('event_id').' = '.$db->quote($id));
		$db->setQuery($query);
		
		$result = $db->execute();
	}
	
	public static function getTvmEvents($category = 0) {
		
		date_default_timezone_set("Europe/Berlin"); 
		
		// Obtain a database connection
		$db = JFactory::getDbo();
		// Retrieve the shout
		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('b.username','a.category','a.id','a.date','a.starttime','a.duration','a.title','a.max_users','a.deadline','a.location','a.event_comment','a.published', 'a.periodic','a.periodic_value', 'a.closed')));
        $query->from($db->quoteName('#__tvm_events','a'));
		$query->join('INNER',$db->quoteName('#__users','b') . ' ON (' . $db->quoteName('a.user_id') . ' = ' . $db->quoteName('b.id').')');
		#$query->where($db->quoteName('a.published').' = '.$db->quote('1').' AND '.$db->quoteName('a.date').' >= '. $db->quote(date("Y-m-d")).' AND '.$db->quoteName('a.date').' <= '. $db->quote(date("Y-m-d", time()+ (7 * 24 * 60 * 60))).' AND '.$db->quoteName('a.category').' = '.$db->quote($category));
		$query->where($db->quoteName('a.published').' = '.$db->quote('1').' AND '.$db->quoteName('a.date').' >= '. $db->quote(date("Y-m-d")).' AND '.$db->quoteName('a.category').' = '.$db->quote($category));
		$query->order($db->quoteName('a.date') . ','. $db->quoteName('a.starttime') .' ASC');
		// 		$query->where($db->quoteName('a.published').' = '.$db->quote('1').' AND ('.$db->quoteName('a.date').' >= '. $db->quote(date("Y-m-d")).' AND '.$db->quoteName('a.date').' <= '. $db->quote(date("Y-m-d", time()+ (7 * 24 * 60 * 60))).') OR ('.$db->quoteName('a.periodic').' != '.$db->quote('none').' AND '.$db->quoteName('a.periodic_value').' != '.date('w').' )');
		$db->setQuery($query);
		
		// Load the row.
		$result = $db->loadObjectList();
		// Return the Hello
		return $result;
	}
	
		public static function getActiveTvmUserByEvent($event_id = 0) {
		// Obtain a database connection
		$db = JFactory::getDbo();
		// Retrieve the shout
		$query = $db->getQuery(true);
 
		$query->select($db->quoteName(array('b.username','a.user_id','a.state','a.acknowledged','a.comment')));
        $query->from($db->quoteName('#__tvm_entry','a'));
		$query->join('INNER',$db->quoteName('#__users','b') . ' ON (' . $db->quoteName('a.user_id') . ' = ' . $db->quoteName('b.id').')');
		$query->where($db->quoteName('a.event_id').' = '.$db->quote($event_id).' AND '.$db->quoteName('a.published').' = 1'.' AND '.$db->quoteName('a.state').' < 3');
		$query->order($db->quoteName('a.state') . ' ASC');
		
		$db->setQuery($query);
		
		// Load the row.
		$result = $db->loadObjectList();
		// Return the Hello
		return $result;
	}
	
	public static function getTvmUserByEvent($event_id = 0) {
		// Obtain a database connection
		$db = JFactory::getDbo();
		// Retrieve the shout
		$query = $db->getQuery(true);
 
		$query->select($db->quoteName(array('b.username','a.user_id','a.state','a.acknowledged','a.comment')));
        $query->from($db->quoteName('#__tvm_entry','a'));
		$query->join('INNER',$db->quoteName('#__users','b') . ' ON (' . $db->quoteName('a.user_id') . ' = ' . $db->quoteName('b.id').')');
		$query->where($db->quoteName('a.event_id').' = '.$db->quote($event_id).' AND '.$db->quoteName('a.published').' = 1');
		$query->order($db->quoteName('a.state') . ' ASC');
		
		$db->setQuery($query);
		
		// Load the row.
		$result = $db->loadObjectList();
		// Return the Hello
		return $result;
	}
	
	public static function getTvmEventIDsOfUser($user_id = 0) {
		// Obtain a database connection
		$db = JFactory::getDbo();
		// Retrieve the shout
		$query = $db->getQuery(true);
 
		$query->select($db->quoteName(array('a.event_id','a.state','a.acknowledged','a.comment')));
        $query->from($db->quoteName('#__tvm_entry','a'));
		$query->where($db->quoteName('user_id').' = '.$db->quote($user_id).' AND '.$db->quoteName('a.published').' = 1');
		$db->setQuery($query);
		// Load the row.
		$result = $db->loadObjectList();
		// Return the Hello
		return $result;
	}
	
	public static function CheckUserLoginAjax(){ 
		$input = JFactory::getApplication()->input;		
		$formData  = $input->get('data', array(), 'array');
		
		$username = $formData['user'];
		$password = $formData['password'];
		
		/*if (!class_exists("JFactory")) {
			define('_JEXEC', 1);
			define('JPATH_BASE', dirname(__FILE__)); // specify path to joomla base directory here
			define('DS', DIRECTORY_SEPARATOR);

			require_once ( JPATH_BASE . DS . 'includes' . DS . 'defines.php' );
			require_once ( JPATH_BASE . DS . 'includes' . DS . 'framework.php' );

			$mainframe = & JFactory::getApplication('site');
			$mainframe->initialise();
		} */

		return $username.' '.$password.'\n\r';
		
		//$user = JFactory::getUser('Jacob'); // or: getUser($id) to get the user with ID $id
		//var_dump($user);
		//$passwordMatch = JUserHelper::verifyPassword($entered_password, $user->password, $user->id);
		//return $passwordMatch;
	}
}
?>