<?php 
// No direct access
defined('_JEXEC') or die; ?>

<?php 
	//JHTML::_('behavior.tooltip');
	jimport( 'joomla.application.module.helper' );
	
	date_default_timezone_set("Europe/Berlin"); 
	
	/* Load CSS and script files to the document */
	$document = JFactory::getDocument();
	$document->addStyleSheet('modules/mod_tvm/css/mod_tvm.css');
	$document->addScript('modules/mod_tvm/js/mod_tvm.js');
	
	/* define colors in html rgb schema */
	$colorS1 = JComponentHelper::getParams('com_tvm')->get('tvm_frontpage_color_state_1'); // ja
	$colorS2 = JComponentHelper::getParams('com_tvm')->get('tvm_frontpage_color_state_2'); // vtl
	$colorS3 = JComponentHelper::getParams('com_tvm')->get('tvm_frontpage_color_state_3'); // nein
	$refusal_message = JComponentHelper::getParams('com_tvm')->get('tvm_message_user_refusal'); // ablehnungstechricht
	$contentJS = 'var mod_tvm_color_s1 = "'.$colorS1.'"; ';
	$contentJS .= 'var mod_tvm_color_s2 = "'.$colorS2.'"; ';
	$contentJS .= 'var mod_tvm_color_s3 = "'.$colorS3.'"; ';
	$contentJS .= 'var mod_tvm_reject_message = "'.$refusal_message.'"; ';
	$document->addScriptDeclaration( $contentJS );
	/* temporary variable for the HTML output */
	$output = '';
	/* read module parameters*/
	#$module = JFactory::getApplication()->get('category', $default);
	$category = $params->get('category');
	#var_dump($category);
	if($category == NULL) {
		$category = 0;
	}
	/* load all available events upon the current date */
	$events = modTvmHelper::getTvmEvents($category);
	
	
	/* check if no event found, skip rest of script then*/
	if(count($events) == 0) {
		$output .= '<span>'.JText::_('MOD_TVM_NO_EVENTS').'</span>';
		echo $output;
		return;
	}
	
	/* get access for the currently logged in user */
	$user = JFactory::getUser();
	/* get the events where the current user is registered to highlight the events */
	$userevents = modTvmHelper::getTvmEventIDsOfUser($user->id);
	/* check access rights from com_tvm component if the current user is a member of the trainer group */
	$isTrainer = false;
	if(array_search(JComponentHelper::getParams('com_tvm')->get('tvm_trainer_group'),$user->groups)){
		$isTrainer = true;
	}
	
	$cntYes = 0;
	$cntYesNACK = 0;
	$cntNo = 0;
	$cntMaybe = 0;
	
	/* initiate an empty array containing the users of a single event for printing */
	$eventuser = null;

	/* variable that held the state of the current user for a single event */
	$state = 0;
	
	$tage = array("So", "Mo", "Di", "Mi", "Do", "Fr", "Sa");
	
	$cssBGColor = "";
	$cssOpacity = "";
	
	$activeUsers = 0;
	/* loop trough all found events */
	foreach($events as $event) {
		/* get all users that are registered for the single event */
		$eventusers = modTvmHelper::getTvmUserByEvent($event->id);
		/* find the users' state of this single event ans set the background color for the surrounding div container */
		foreach($userevents as $userevent) {
			if($userevent->event_id == $event->id) {
				switch($userevent->state) {
					case '1': // Ja
						$state = 1;
						$cssBGColor = $colorS1;
							if(!$userevent->acknowledged){
								//$cssOpacity = 'background: repeating-linear-gradient( 45deg, '.$cssBGColor.', '.$cssBGColor.', 10px, #ccc 10px, #ccc 20px  );';
								$cssBGColor = "#CCCCCC";
							}
						$activeUsers++;
						break;
					case '2': // vielleicht
						$state = 2;
						$cssBGColor = $colorS2;
						$activeUsers++;
						break;
					case '3': // nein
						$state = 3;
						$cssBGColor = $colorS3;
						break;
					case '4': // forced no
						$state = 4;
						$cssBGColor = $colorS3;
						break;
					default:
						break;
				}

				break;
			} 
			
		}
		
		/* build the output */
		$output .= '<div class="mod_tvm" style="background:'.$cssBGColor.'; '.$cssOpacity.'">';
		$output .= '<span>'.$event->title.' - '.$event->username.'</span>&nbsp;<a class="XXX_mod_tvm_get_ical" role="button" id="'.$event->id.'" href="modules/mod_tvm/ical.php?event_id='.$event->id.'">&#x1F4C5;</a><br />';
		$output .= '<span>Wo?</span> '.$event->location.'<br />';
		$output .= '<span>Wann?</span> '. $tage[date("w", strtotime($event->date))].' '. date("d.m.Y", strtotime($event->date)).' '. $event->starttime.'<br />';
		if(strlen($event->event_comment) > 0) {
			$output .= '<span>&rArr; &nbsp;</span>'.$event->event_comment.'<br />';
		}
		$output .= '<form class="mod_tvm_input"><input type="hidden" value="'.$event->id.'" name="id"><input type="hidden" value="'.$user->id.'" name="user_id">';
		$closed = false;
		/* Check deadline */
		/*if(date_parse($event->date) == date_parse(date("d.m.Y"))){
			if((strtotime($event->starttime)-time())/60 < $event->deadline) {
				$closed = true;
			}
		}*/
		if(((strtotime($event->date.' '.$event->starttime)) - ($event->deadline * 60) ) < time()){
			$closed = true;
		}
		//var_dump($activeUsers);
		/* check available tickets */
		if(($activeUsers) >= $event->max_users) {
			$closed = true;
		}
		
		if($event->closed == 1){
			$closed = true;		// event closed
		}
		
		if($state == 4) { // forced no for current user
			$closed = true;
		}
		
		if($closed){
			$output .= '<span>Anmeldung geschlossen.</span>';
		} else {
			$output .= '<input type="radio" value="ja" name="status" title="ja" '.($state == 1 ? 'checked':'').' /> Ja ';
			$output .= '<input type="radio" value="vlt" name="status" title="vlt" '.($state == 2 ? 'checked':'').' /> Vielleicht ';
			$output .= '<input type="radio" value="nein" name="status" title="nein" '.(($state == 3 || $state == 4) ? 'checked':'').' /> Nein ';
		}
		
		$output .= '</form>';
		/* here starts the list of users of this event */
		$output .= '<a role="button" tabindex="0" name="expand"><span class="mod_tvm_expand"><b>+Ausklappen</b></span></a>'; 
		$output .= '<div class="mod_tvm_list">';
		$output .= '<form class="mod_tvm_input_comment"><input type="hidden" value="'.$event->id.'" name="event_id"><input type="hidden" value="'.$user->id.'" name="user_id"><input type="text" name="comment" style="width: 89%; font-size:10pt; height: 12pt; margin: 0px; padding: 0px;" value="'.($state > 0 ? $userevent->comment : '').'" /><input style="height: 12pt; width: 9%;" type="button" name="btnComment" value="&#x2714;" /></form>';
		$output .= '<table class="mod_tvm_list_table" style="width: 100%;"><tr><th style="width: 85%;">Name</th><th style="width: 5%;">&#x2714;</th>';
		/* add an acknowledgement column if currently logged-in user is in trainer group */
		if($isTrainer){
			$output .= '<th style="width: 5%;">&#x2714;?</th><th>&#10008;</th>';
		}	
		$output .= '</tr>';
			
		foreach($eventusers as $eventuser) {
			$listuserBGColor = '#CCCCCC';
			switch($eventuser->state) {
				case '1':
					if($eventuser->acknowledged == '1'){
						$listuserBGColor = $colorS1;
						$cntYes++;
					} else {
						$cntYesNACK++;
					}
					break;
				case '2':
					$listuserBGColor = $colorS2;
					$cntMaybe++;
					break;
				case '3':
				case '4':
					$listuserBGColor = $colorS3;
					$cntNo++;
					break;
				default:
					break;
			}
			
			$output .= '<tr style="background-color: '.$listuserBGColor.';">';
			$output .= '<td>'.$eventuser->username.'</td>';
			// <a class="hasTip" href="#" alt="Best&auml;tigen" title="Best&auml;tigen">&#x2714</a>
			$output .= '<td class="mod_tvm_check_ack">'.($eventuser->acknowledged == '1' ? '&#x2714;' : '?').'</td>'; // is set if acknowledged
			if($isTrainer){
				$output .= '<td><form><input type="hidden" name="user_id" value="'.$eventuser->user_id.'" /><input type="hidden" name="event_id" value="'.$event->id.'" /><input type="hidden" name="editor_id" value="'.$user->id.'" /><input type="checkbox" name="ack" '.($eventuser->acknowledged == '1' ? 'checked' : 'unchecked').'></input></form></td>';
				$output .= '<td><a>&#10008;</a></td>';
			}
			$output .= '</tr>';
			/* if a comment fot this user entry is present create a single row in the output table */
			if(strlen($eventuser->comment) > 0){
				$output .= '<td colspan="'.($isTrainer ? '4' : '2').'" style="background-color: '.$listuserBGColor.';"><span>&rArr; &nbsp;</span>'.$eventuser->comment.'</td></tr>';
			}
		}
		$output .= '</table>';
		$output .= '<div style="text-align: center"><span style="width: 100%; text-align: center;">Ja: '.$cntYes.'('.($cntYes+$cntYesNACK).') - Nein: '.$cntNo.' - Vielleicht: '.$cntMaybe.'</span><br />';
		if($isTrainer) {
			$output .= '<a style="padding-left:10px;" class="mod_tvm_refuse_event" id="'.$event->id.'">Training absagen!</a><br />';
			$output .= '<a style="padding-left:10px;" class="mod_tvm_send_mail_to_all" id="'.$event->id.'">Mail an alle!</a>';
		}
		$output .= '</div></div>';
		$output .= '</div>';
		
		/* reset temporary variables for the next loop run*/
		$state = 0;
		$cntYes = 0;
		$cntNo = 0;
		$cntYesNACK = 0;
		$cntMaybe = 0;
		$cssBGColor = "";
		$cssOpacity = "";
	}
	
	
	echo $output;
?>
