jQuery.noConflict()(function ($) { 
	$(document).ready(function(){
		$('.mod_tvm_input input').on('change', function() {
            $elem = $(this);
            $id = $elem.parent().children("input[name=id]").val();
			$userid = $elem.parent().children("input[name=user_id]").val();
			$comment = $(this).parent().next().next().children("form").children("input[name=comment]").val();
            // alert("ID: " + $id + " selection: " + $elem.val());
			// Assign handlers immediately after making the request,
			// and remember the jqxhr object for this request
			// todo: user ID
			if($userid > 0) {
				var value   = {'id': String($id), 'selection': String($elem.val()), 'user_id': String($userid),'comment':String($comment)};
				var request = {
						'option' : 'com_ajax',
						'module' : 'tvm',
						'data'   : value,
						'format' : 'raw'
					};
					$.ajax({
					type   : 'POST',
					data   : request,
					success: function (response) {
						// Update background color
//						alert($elem.parent().parent().html());
						switch(response){
							case '1': // Yes
								$elem.parent().parent().css("background-color","#CCCCCC");
								//$elem.parent().parent().css("background-color", mod_tvm_color_s1);
								//$elem.parent().parent().css("background", "repeating-linear-gradient( 45deg, "+mod_tvm_color_s1+", "+mod_tvm_color_s1+", 10px, #ccc 10px, #ccc 20px  );");
								break;
							case '2': // Maybe
								$elem.parent().parent().css("background-color", mod_tvm_color_s2);
								//$elem.parent().parent().css("background", "repeating-linear-gradient( 45deg, "+mod_tvm_color_s3+", "+mod_tvm_color_s3+", 10px, #ccc 10px, #ccc 20px  );");
								break;
							case '3': // No
								$elem.parent().parent().css("background-color", mod_tvm_color_s3);
								//$elem.parent().parent().css("background", "repeating-linear-gradient( 45deg, "+mod_tvm_color_s2+", "+mod_tvm_color_s2+", 10px, #ccc 10px, #ccc 20px  );");
								break;
							default:
								break;
						}
					}
				});
				return true;	
			} else {
				return false;
			}
		});
		
		$('.mod_tvm a[name=expand]').on('click', function() {
			$link = $(this);
			$divtag = $link.parent().children('div.mod_tvm_list');
			$divtag.toggle('fast',function(){
				if($divtag.is(':visible')){
					$link.text('- Verstecken');
				} else {
					$link.text('+ Ausklappen');
				}
			});
			
		});
		
		$('.mod_tvm_list_table input').on('change',function(){
			$elem = $(this);
			$event_id = $elem.parent().children('input[name="event_id"]').val();
			$user_id = $elem.parent().children('input[name="user_id"]').val();
			$editor_id = $elem.parent().children('input[name="editor_id"]').val();
			//$ack = String($elem.attr('checked')).localeCompare('checked') == '0'? 1 : 0; // if element is 'checked' localCompare return 0 but we want 1 in this case
			$ack = $elem.is(':checked') ? 1 : 0;			
			//alert($event_id + " " + $user_id + " " + $ack);
			var value   = {'event_id': String($event_id), 'user_id': String($user_id), 'ack': String($ack), 'editor_id' : String($editor_id) };
				var request = {
						'option' : 'com_ajax',
						'module' : 'tvm',
						'data'   : value,
						'format' : 'raw',
						'method' : 'setAck'
					};
					$.ajax({
					type   : 'POST',
					data   : request,
					success: function (response) {
						if($ack == '1') {
							$elem.parents('tr').children('td.mod_tvm_check_ack').html('&#x2714;');							
							//$elem.parents('tr').css("background-color", mod_tvm_color_s1);
						} else {
							$elem.parents('tr').children('td.mod_tvm_check_ack').html('?');
						}
					}
				});
			return true;	
		});
		$('.mod_tvm_list_table a').on('click',function(){
			$elem = $(this);
			$event_id = $elem.parents('tr').children('td:eq(2)').children('form').children('input[name="event_id"]').val();
			$user_id = $elem.parents('tr').children('td:eq(2)').children('form').children('input[name="user_id"]').val();
			$editor_id = $elem.parents('tr').children('td:eq(2)').children('form').children('input[name="editor_id"]').val();
			//alert($elem.parents('tr').children('td:eq(2)').children('form').html());
			//alert($event_id + " " + $user_id + " " + $editor_id );
			if(window.confirm("Sicher? Der User wird abgemeldet und per E-Mail informiert.")){
				/*variable $refusal_message defined in tmpl/default.php*/
				$mailMsg = window.prompt("E-Mail Text:",mod_tvm_reject_message);
				if($mailMsg.length > 0) {
					var value   = {'event_id': String($event_id), 'user_id': String($user_id), 'editor_id' : String($editor_id), 'mail_message' : String($mailMsg) };
					var request = {
							'option' : 'com_ajax',
							'module' : 'tvm',
							'data'   : value,
							'format' : 'raw',
							'method' : 'removeUserFromEvent'
						};
						$.ajax({
						type   : 'POST',
						data   : request,
						success: function (response) {
							location.reload();
						}
					});
					return true;	
				} else {
					return false;
				}
			} else {
				return false;
			}
			
		});
		
		$('a.mod_tvm_send_mail_to_all').on('click',function(){
			$elem = $(this);
			$event_id = $elem.attr('id');
			//alert($elem.parents('tr').children('td:eq(2)').children('form').html());
			//alert($event_id + " " + $user_id + " " + $editor_id );
			if(window.confirm("Sicher? Text wird an alle gemeldeten User gesendet!")){
				/*variable $refusal_message defined in tmpl/default.php*/
				$mailMsg = window.prompt("E-Mail Text:",mod_tvm_reject_message);
				if($mailMsg.length > 0) {
					var value   = {'event_id': String($event_id), 'mail_message' : String($mailMsg) };
					var request = {
							'option' : 'com_ajax',
							'module' : 'tvm',
							'data'   : value,
							'format' : 'raw',
							'method' : 'sendMailToUsers'
						};
						$.ajax({
						type   : 'POST',
						data   : request,
						success: function (response) {
							alert("Done");
						}
					});
					return true;	
				} else {
					return false;
				}
			} else {
				return false;
			}
			
		});
		
		$('a.mod_tvm_refuse_event').on('click',function(){
			$elem = $(this);
			$event_id = $elem.attr('id');
			if(window.confirm("Sicher? Alle angemeldeten User erhalten eine Absage per E-Mail.")){				
				var value   = {'event_id': String($event_id) };
				var request = {
						'option' : 'com_ajax',
						'module' : 'tvm',
						'data'   : value,
						'format' : 'raw',
						'method' : 'refuseEvent'
					};
					$.ajax({
					type   : 'POST',
					data   : request,
					success: function (response) {
						alert(response);
					}
				});
				return true;			
			} else {
				return false;
			}
		});
		/*
		$('a.mod_tvm_get_ical').on('click',function(){
			$elem = $(this);
			$event_id = $elem.attr('id');
			//alert($event_id);
			
			var value   = {'event_id': String($event_id)};
				var request = {
						'option' : 'com_ajax',
						'module' : 'tvm',
						'data'   : value,
						'format' : 'raw',
						'method' : 'getIcal'
					};
					$.ajax({
					type   : 'GET',
					data   : request,
					success: function (response) {
						//document.location='data:text/calendar;charset=UTF-8;filename=calendar.ics;,' + encodeURIComponent(response)
						var link = document.createElement('a');
						link.download = "calendar.ics";
						link.href = 'data:text/calendar;charset=UTF-8;filename=calendar.ics;,' + encodeURIComponent(response)
						link.click();
					}
				});
			return true;	
		});
		*/
		
		$('.mod_tvm_input_comment input:button').on('click',function(){
			$elem = $(this);
			$event_id = $elem.parent().children('input[name="event_id"]').val();
			$user_id = $elem.parent().children('input[name="user_id"]').val();
			$comment =  $elem.parent().children('input[name="comment"]').val();
			//alert($event_id + " " + $user_id + " " + $comment);
			
			var value   = {'event_id': String($event_id), 'user_id': String($user_id), 'comment': String($comment)};
				var request = {
						'option' : 'com_ajax',
						'module' : 'tvm',
						'data'   : value,
						'format' : 'raw',
						'method' : 'setComment'
					};
					$.ajax({
					type   : 'POST',
					data   : request,
					success: function (response) {
						location.reload(true); // reload to update lists
					}
				});
			return true;	
		});
	});
});