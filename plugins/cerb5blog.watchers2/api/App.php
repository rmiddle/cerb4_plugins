<?php
class ChWatchersConfigTab extends Extension_ConfigTab {
	const ID = 'watchers.config.tab';
	
	function showTab() {
		$settings = CerberusSettings::getInstance();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$core_tplpath = dirname(dirname(dirname(__FILE__))) . '/cerberusweb.core/templates/';
		$tpl->assign('core_tplpath', $core_tplpath);
		$tpl->cache_lifetime = "0";

		$tpl->assign('response_uri', 'config/watchers');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'C4_WatcherView';
		$defaults->id = C4_WatcherView::DEFAULT_ID;
		$defaults->renderSortBy = SearchFields_WatcherMailFilter::POS;
		$defaults->renderSortAsc = 0;
		
		$view = C4_AbstractViewLoader::getView(C4_WatcherView::DEFAULT_ID, $defaults);
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_WatcherView::getFields());
		$tpl->assign('view_searchable_fields', C4_WatcherView::getSearchFields());
		
		$tpl->display('file:' . $tpl_path . 'config/watchers/index.tpl');
	}
	
	function saveTab() {
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string');

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','watchers')));
		exit;
	}
	
};

class ChWatchersEventListener extends DevblocksEventListenerExtension {
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {
        switch($event->id) {
            case 'bucket.delete':
				$this->_bucketDeleted($event);
            	break;
            	
            case 'group.delete':
				$this->_groupDeleted($event);
            	break;

            case 'ticket.property.pre_change':
				$this->_workerAssigned($event);
            	break;
				
			case 'ticket.comment.create':
				$this->_newTicketComment($event);
				break;
				
            case 'ticket.reply.inbound':
				$this->_sendForwards($event, true);
            	break;
            	
            case 'ticket.reply.outbound':
				$this->_sendForwards($event, false);
            	break;
            	
            case 'worker.delete':
				$this->_workerDeleted($event);
            	break;
        }
    }

	private function _getMailingListFromMatches($matches) {
		$workers = DAO_Worker::getAllActive();
		$helpdesk_senders = CerberusApplication::getHelpdeskSenders();
		
		$notify_emails = array();
		
		if(is_array($matches))
		foreach($matches as $filter) {
			if(!$filter instanceof Model_WatcherMailFilter)
				continue;
			
			// If the worker no longer exists or is disabled
			if(!isset($workers[$filter->worker_id]))
				continue;
				
			if(isset($filter->actions['email']['to']) && is_array($filter->actions['email']['to']))
			foreach($filter->actions['email']['to'] as $addy) {
				$addy = strtolower($addy);
				
				// Don't allow a worker to usurp a helpdesk address
				if(isset($helpdesk_senders[$addy]))
					continue;
				
				if(!isset($notify_emails[$addy]))
					$notify_emails[$addy] = $addy;
			}
		}
		
		return $notify_emails;
	}

	private function _sendNotifications($filters, $url, $msg) {
		if(is_array($filters))
		foreach($filters as $idx => $filter) { /* @var $filter Model_WatcherMailFilter */
			if(isset($filter->actions['notify'])) {
				$fields = array(
					DAO_WorkerEvent::CREATED_DATE => time(),
					DAO_WorkerEvent::WORKER_ID => $filter->worker_id,
					DAO_WorkerEvent::URL => $url,
					DAO_WorkerEvent::TITLE => 'Watcher: '. $filter->name,
					DAO_WorkerEvent::CONTENT => $msg,
					DAO_WorkerEvent::IS_READ => 0,
				);
				DAO_WorkerEvent::create($fields);
			}
		}
	}

	private function _newTicketComment($event) {
		@$comment_id = $event->params['comment_id'];
		@$ticket_id = $event->params['ticket_id'];
		@$address_id = $event->params['address_id'];
		@$comment = $event->params['comment'];
    	
    	if(empty($ticket_id) || empty($address_id) || empty($comment))
    		return;
    		
		// Resolve the address ID
		if(null == ($address = DAO_Address::get($address_id)))
			return;
			
		// Try to associate the author with a worker
		if(null == ($worker_addy = DAO_AddressToWorker::getByAddress($address->email)))
			return;
				
		if(null == ($worker = DAO_Worker::getAgent($worker_addy->worker_id)))
			return;
			
		$url_writer = DevblocksPlatform::getUrlService();

		$mail_service = DevblocksPlatform::getMailService();
		$mailer = null; // lazy load
    		
    	$settings = CerberusSettings::getInstance();
		$default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM, '');
		$default_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL, '');

		if(null == ($ticket = DAO_Ticket::getTicket($ticket_id)))
			return;

		// Find all our matching filters
		if(empty($ticket) || false == ($matches = Model_WatcherMailFilter::getMatches(
			$ticket,
			'ticket_comment'
		)))
			return;
		
		// Remove any matches from the author
		if(is_array($matches))
		foreach($matches as $idx => $filter) {
			if($filter->worker_id == $worker_addy->worker_id)
				unset($matches[$idx]);
		}
		
		// (Action) Send Notification

		$this->_sendNotifications(
			$matches,
			$url_writer->write('c=display&mask=' . $ticket->mask, true, false),
			sprintf("[Ticket] %s", $ticket->subject)
		);
		
		// (Action) Forward E-mail:
		
		// Sanitize and combine all the destination addresses
		$notify_emails = $this->_getMailingListFromMatches($matches);
		
		if(empty($notify_emails))
			return;
			
		if(null == (@$last_message = end($ticket->getMessages()))) { /* @var $last_message CerberusMessage */
			continue;
		}
		
		if(null == (@$last_headers = $last_message->getHeaders()))
			continue;
			
		$reply_to = $default_from;
		$reply_personal = $default_personal;
			
		// See if we need a group-specific reply-to
		if(!empty($ticket->team_id)) {
			@$group_from = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_REPLY_FROM);
			if(!empty($group_from))
				$reply_to = $group_from;
				
			@$group_personal = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_REPLY_PERSONAL);
			if(!empty($group_personal))
				$reply_personal = $group_personal;
		}
		
		if(is_array($notify_emails))
		foreach($notify_emails as $send_to) {
	    	try {
	    		if(null == $mailer)
					$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
				
		 		// Create the message
					
				$mail = $mail_service->createMessage();
				$mail->setTo(array($send_to));
				$mail->setFrom(array($reply_to => $reply_personal));
				$mail->setReplyTo($reply_to);
				$mail->setSubject(sprintf("[comment #%s]: %s [comment]",
					$ticket->mask,
					$ticket->subject
				));
			
				$headers = $mail->getHeaders();
			
				if(false !== (@$in_reply_to = $last_headers['in-reply-to'])) {
				    $headers->addTextHeader('References', $in_reply_to);
				    $headers->addTextHeader('In-Reply-To', $in_reply_to);
				}
				
				// Build the body
				$comment_text = sprintf("%s (%s) comments:\r\n%s\r\n",
					$worker->getName(),
					$address->email,
					$comment
				);
				
				$headers->addTextHeader('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
				$headers->addTextHeader('Precedence','List');
				$headers->addTextHeader('Auto-Submitted','auto-generated');
				
				$mail->setBody($comment_text);
			
				$result = $mailer->send($mail);
				
	    	} catch(Exception $e) {
	    		//
			}
		}
	}

    private function _workerAssigned($event) {
    	@$ticket_ids = $event->params['ticket_ids'];
    	@$changed_fields = $event->params['changed_fields'];
    	
    	if(empty($ticket_ids) || empty($changed_fields))
    		return;
    		
    	@$next_worker_id = $changed_fields[DAO_Ticket::NEXT_WORKER_ID];

    	// Make sure a next worker was assigned
    	if(empty($next_worker_id))
    		return;

    	@$active_worker = CerberusApplication::getActiveWorker();
    		
    	// Make sure we're not assigning work to ourselves, if so then bail
    	if(null != $active_worker && $active_worker->id == $next_worker_id) {
    		return;
    	}

    	$url_writer = DevblocksPlatform::getUrlService();
    	
		$mail_service = DevblocksPlatform::getMailService();
		$mailer = null; // lazy load
    		
    	$settings = CerberusSettings::getInstance();
		$default_from = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM, '');
		$default_personal = $settings->get(CerberusSettings::DEFAULT_REPLY_PERSONAL, '');

		// Loop through all assigned tickets
		$tickets = DAO_Ticket::getTickets($ticket_ids);
		foreach($tickets as $ticket) { /* @var $ticket CerberusTicket */
			// If the next worker value didn't change, skip
			if($ticket->next_worker_id == $next_worker_id)
				continue;
			
			// Find all our matching filters
			if(empty($ticket) || false == ($matches = Model_WatcherMailFilter::getMatches(
				$ticket,
				'ticket_assignment',
				$next_worker_id
			)))
				return;

			// (Action) Send Notification

			$this->_sendNotifications(
				$matches,
				$url_writer->write('c=display&mask=' . $ticket->mask, true, false),
				sprintf("[Ticket] %s", $ticket->subject)
			);

			// (Action) Forward Email To:

			// Sanitize and combine all the destination addresses
			$notify_emails = $this->_getMailingListFromMatches($matches);
			
			if(empty($notify_emails))
				return;
				
			if(null == (@$last_message = end($ticket->getMessages()))) { /* @var $last_message CerberusMessage */
				continue;
			}
			
			if(null == (@$last_headers = $last_message->getHeaders()))
				continue;
				
			$reply_to = $default_from;
			$reply_personal = $default_personal;
				
			// See if we need a group-specific reply-to
			if(!empty($ticket->team_id)) {
				@$group_from = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_REPLY_FROM);
				if(!empty($group_from))
					$reply_to = $group_from;
					
				@$group_personal = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_REPLY_PERSONAL);
				if(!empty($group_personal))
					$reply_personal = $group_personal;
			}
			
			if(is_array($notify_emails))
			foreach($notify_emails as $send_to) {
		    	try {
		    		if(null == $mailer)
						$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
					
			 		// Create the message

					$mail = $mail_service->createMessage();
					$mail->setTo(array($send_to));
					$mail->setFrom(array($reply_to => $reply_personal));
					$mail->setReplyTo($reply_to);
					$mail->setSubject(sprintf("[assignment #%s]: %s",
						$ticket->mask,
						$ticket->subject
					));
				
					$headers = $mail->getHeaders();
				
					if(false !== (@$in_reply_to = $last_headers['in-reply-to'])) {
					    $headers->addTextHeader('References', $in_reply_to);
					    $headers->addTextHeader('In-Reply-To', $in_reply_to);
					}
					
					$headers->addTextHeader('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
					$headers->addTextHeader('Precedence','List');
					$headers->addTextHeader('Auto-Submitted','auto-generated');
					
					$mail->setBody($last_message->getContent());					
				
					$result = $mailer->send($mail);
					
		    	} catch(Exception $e) {
		    		//
				}
			}
		}
    }
    
    private function _workerDeleted($event) {
    	@$worker_ids = $event->params['worker_ids'];
    	DAO_WatcherMailFilter::deleteByWorkerIds($worker_ids);
    }
    
    private function _bucketDeleted($event) {
    	@$bucket_ids = $event->params['bucket_ids'];
    	DAO_WatcherMailFilter::deleteByBucketIds($bucket_ids);
    }
    
    private function _groupDeleted($event) {
    	@$group_ids = $event->params['group_ids'];
    	DAO_WatcherMailFilter::deleteByGroupIds($group_ids);
    }
    
    private function _sendForwards($event, $is_inbound) {
        @$ticket_id = $event->params['ticket_id'];
        @$send_worker_id = $event->params['worker_id'];
    	
		$url_writer = DevblocksPlatform::getUrlService();
		
		$ticket = DAO_Ticket::getTicket($ticket_id);

		// Find all our matching filters
		if(empty($ticket) || false == ($matches = Model_WatcherMailFilter::getMatches(
			$ticket,
			($is_inbound ? 'mail_incoming' : 'mail_outgoing')
		)))
			return;
		
		// (Action) Send Notification
		
		$this->_sendNotifications(
			$matches,
			$url_writer->write('c=display&mask=' . $ticket->mask, true, false),
			sprintf("[Ticket] %s", $ticket->subject)
		);
		
		// (Action) Forward Email To:
		
		// Sanitize and combine all the destination addresses
		$notify_emails = $this->_getMailingListFromMatches($matches);
		
		if(empty($notify_emails))
			return;
		
		// [TODO] This could be more efficient
		$messages = DAO_Ticket::getMessagesByTicket($ticket_id);
		$message = end($messages); // last message
		unset($messages);
		$headers = $message->getHeaders();
			
		// The whole flipping Swift section needs wrapped to catch exceptions
		try {
			$settings = CerberusSettings::getInstance();
			$reply_to = $settings->get(CerberusSettings::DEFAULT_REPLY_FROM, '');
			
			// See if we need a group-specific reply-to
			if(!empty($ticket->team_id)) {
				@$group_from = DAO_GroupSettings::get($ticket->team_id, DAO_GroupSettings::SETTING_REPLY_FROM, '');
				if(!empty($group_from))
					$reply_to = $group_from;
			}
			
			$sender = DAO_Address::get($message->address_id);
	
			$sender_email = strtolower($sender->email);
			$sender_split = explode('@', $sender_email);
	
			if(!is_array($sender_split) || count($sender_split) != 2)
				return;
	
			// If return-path is blank
			if(isset($headers['return-path']) && $headers['return-path'] == '<>')
				return;
				
			// Ignore bounces
			if($sender_split[0]=="postmaster" || $sender_split[0] == "mailer-daemon")
				return;
			
			// Ignore autoresponses autoresponses
			if(isset($headers['auto-submitted']) && $headers['auto-submitted'] != 'no')
				return;
				
			// Attachments
			$attachments = $message->getAttachments();
			$mime_attachments = array();
			if(is_array($attachments))
			foreach($attachments as $attachment) {
				if(0 == strcasecmp($attachment->display_name,'original_message.html'))
					continue;
					
				$attachment_path = APP_STORAGE_PATH . '/attachments/'; // [TODO] This is highly redundant in the codebase
				if(!file_exists($attachment_path . $attachment->filepath))
					continue;
				
				$attach = Swift_Attachment::fromPath($attachment_path . $attachment->filepath);
				if(!empty($attachment->display_name))
					$attach->setFilename($attachment->display_name);
				$mime_attachments[] = $attach;
			}
	    	
	    	// Send copies
			if(is_array($notify_emails) && !empty($notify_emails)) {
				$mail_service = DevblocksPlatform::getMailService();
				$mailer = $mail_service->getMailer(CerberusMail::getMailerDefaults());
				
				foreach($notify_emails as $to) {
					// Proxy the message
					
					$mail = $mail_service->createMessage(); /* @var $mail Swift_Message */
					$mail->setTo(array($to));
					$mail->setFrom(array($sender->email));
					$mail->setReplyTo($reply_to);
					$mail->setReturnPath($reply_to);
					$mail->setSubject(sprintf("[%s #%s]: %s",
						($is_inbound ? 'inbound' : 'outbound'),
						$ticket->mask,
						$ticket->subject
					));

					$hdrs = $mail->getHeaders();
					
					if(null !== (@$msgid = $headers['message-id'])) {
						$hdrs->addTextHeader('Message-Id',$msgid);
					}
					
					if(null !== (@$in_reply_to = $headers['in-reply-to'])) {
					    $hdrs->addTextHeader('References', $in_reply_to);
					    $hdrs->addTextHeader('In-Reply-To', $in_reply_to);
					}
					
					$hdrs->addTextHeader('X-Mailer','Cerberus Helpdesk (Build '.APP_BUILD.')');
					$hdrs->addTextHeader('Precedence','List');
					$hdrs->addTextHeader('Auto-Submitted','auto-generated');
					
					$mail->setBody($message->getContent());
	
					// Send message attachments with watcher
					if(is_array($mime_attachments))
					foreach($mime_attachments as $mime_attachment) {
						$mail->attach($mime_attachment);
					}
				
					$result = $mailer->send($mail);
				}
			}
		}
		catch(Exception $e) {
			if(!empty($message_id)) {
				$fields = array(
					DAO_MessageNote::MESSAGE_ID => $message_id,
					DAO_MessageNote::CREATED => time(),
					DAO_MessageNote::WORKER_ID => 0,
					DAO_MessageNote::CONTENT => 'Exception thrown while sending watcher email: ' . $e->getMessage(),
					DAO_MessageNote::TYPE => Model_MessageNote::TYPE_ERROR,
				);
				DAO_MessageNote::create($fields);
			}
		}
    }
};

class ChWatchersPreferences extends Extension_PreferenceTab {
	private $_TPL_PATH = null; 
	
    function __construct($manifest) {
        parent::__construct($manifest);
        $this->_TPL_PATH = dirname(dirname(__FILE__)).'/templates/';
    }
	
	// Ajax
	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		$tpl->cache_lifetime = "0";
		
		$worker = CerberusApplication::getActiveWorker();
		
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->assign('response_uri', 'preferences/watchers');
		
		if(null == ($view = C4_AbstractViewLoader::getView('prefs.watchers'))) {
			$view = new C4_WatcherView();
			$view->id = 'prefs.watchers';
			$view->name = "My Watcher Filters";
			$view->renderSortBy = SearchFields_WatcherMailFilter::POS;
			$view->renderSortAsc = 0;
			$view->params = array(
				SearchFields_WatcherMailFilter::WORKER_ID => new DevblocksSearchCriteria(SearchFields_WatcherMailFilter::WORKER_ID,'eq',$worker->id),
			);
			
			C4_AbstractViewLoader::setView($view->id, $view);
		}
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_WatcherView::getFields());
		$tpl->assign('view_searchable_fields', C4_WatcherView::getSearchFields());
		
		$tpl->display('file:' . $this->_TPL_PATH . 'preferences/watchers.tpl');
	}
    
	// Post
	function saveTab() {
	}
	
	// Ajax
	function showWatcherBulkPanelAction() {
		@$id_csv = DevblocksPlatform::importGPC($_REQUEST['ids']);
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id']);

		$tpl = DevblocksPlatform::getTemplateService();
		$path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->assign('path', $path);
		$tpl->assign('view_id', $view_id);

	    if(!empty($id_csv)) {
	        $ids = DevblocksPlatform::parseCsvString($id_csv);
	        $tpl->assign('ids', implode(',', $ids));
	    }
		
		// Custom Fields
//		$custom_fields = DAO_CustomField::getBySource(ChCustomFieldSource_TimeEntry::ID);
//		$tpl->assign('custom_fields', $custom_fields);
		
		$tpl->cache_lifetime = "0";
		$tpl->display('file:' . $path . 'preferences/bulk.tpl');
	}
	
	// Ajax
	function doWatcherBulkPanelAction() {
		// Checked rows
	    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
		$ids = DevblocksPlatform::parseCsvString($ids_str);

		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Watcher fields
		@$status = trim(DevblocksPlatform::importGPC($_POST['do_status'],'string',''));

		$do = array();
		
		// Do: ...
		if(0 != strlen($status))
			$do['status'] = intval($status);
			
		// Do: Custom fields
		//$do = DAO_CustomFieldValue::handleBulkPost($do);
			
		$view->doBulkUpdate($filter, $do, $ids);
		
		$view->render();
		return;
	}
	
	// Ajax
	function showWatcherPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null != ($filter = DAO_WatcherMailFilter::get($id))) {
			$tpl->assign('filter', $filter);
		}
		
		$groups = DAO_Group::getAll();
		$tpl->assign('groups', $groups);

		$buckets = DAO_Bucket::getAll();
		$tpl->assign('buckets', $buckets);

		$group_buckets = DAO_Bucket::getTeams();
		$tpl->assign('group_buckets', $group_buckets);
		
		$memberships = $active_worker->getMemberships();
		$tpl->assign('memberships', $memberships);
		
		if(null == (@$worker_id = $filter->worker_id)) {
			$worker_id = $active_worker->id;
		}
		
		$addresses = DAO_AddressToWorker::getByWorker($worker_id);
		$tpl->assign('addresses', $addresses);

		$tpl->assign('workers', DAO_Worker::getAllActive());
		$tpl->assign('all_workers', DAO_Worker::getAll());

		// Custom Fields: Ticket
		$ticket_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Ticket::ID);
		$tpl->assign('ticket_fields', $ticket_fields);

		// Custom Fields: Address
		$address_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Address::ID);
		$tpl->assign('address_fields', $address_fields);
		
		// Custom Fields: Orgs
		$org_fields = DAO_CustomField::getBySource(ChCustomFieldSource_Org::ID);
		$tpl->assign('org_fields', $org_fields);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'preferences/peek.tpl');
	}
	
	function saveWatcherPanelAction() {
   		$translate = DevblocksPlatform::getTranslationService();
   		
   		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
   		
	    @$active_worker = CerberusApplication::getActiveWorker();
//	    if(!$active_worker->is_superuser)
//	    	return;

	    /*****************************/
		@$name = DevblocksPlatform::importGPC($_POST['name'],'string','');
		@$is_disabled = DevblocksPlatform::importGPC($_POST['is_disabled'],'integer',0);
		@$worker_id = DevblocksPlatform::importGPC($_POST['worker_id'],'integer',0);
		@$rules = DevblocksPlatform::importGPC($_POST['rules'],'array',array());
		@$do = DevblocksPlatform::importGPC($_POST['do'],'array',array());
		
		if(empty($name))
			$name = $translate->_('Watcher Filter');
		
		$criterion = array();
		$actions = array();
		
		// Custom fields
		$custom_fields = DAO_CustomField::getAll();
		
		// Criteria
		if(is_array($rules))
		foreach($rules as $rule) {
			$rule = DevblocksPlatform::strAlphaNumDash($rule);
			@$value = DevblocksPlatform::importGPC($_POST['value_'.$rule],'string','');
			
			// [JAS]: Allow empty $value (null/blank checking)
			
			$criteria = array(
				'value' => $value,
			);
			
			// Any special rule handling
			switch($rule) {
				case 'dayofweek':
					// days
					$days = DevblocksPlatform::importGPC($_REQUEST['value_dayofweek'],'array',array());
					if(in_array(0,$days)) $criteria['sun'] = 'Sunday';
					if(in_array(1,$days)) $criteria['mon'] = 'Monday';
					if(in_array(2,$days)) $criteria['tue'] = 'Tuesday';
					if(in_array(3,$days)) $criteria['wed'] = 'Wednesday';
					if(in_array(4,$days)) $criteria['thu'] = 'Thursday';
					if(in_array(5,$days)) $criteria['fri'] = 'Friday';
					if(in_array(6,$days)) $criteria['sat'] = 'Saturday';
					unset($criteria['value']);
					break;
				case 'timeofday':
					$from = DevblocksPlatform::importGPC($_REQUEST['timeofday_from'],'string','');
					$to = DevblocksPlatform::importGPC($_REQUEST['timeofday_to'],'string','');
					$criteria['from'] = $from;
					$criteria['to'] = $to;
					unset($criteria['value']);
					break;
				case 'event':
					@$events = DevblocksPlatform::importGPC($_REQUEST['value_event'],'array',array());
					if(is_array($events))
					foreach($events as $event)
						$criteria[$event] = true;
					unset($criteria['value']);
					break;
				case 'groups':
					@$groups = DevblocksPlatform::importGPC($_REQUEST['value_groups'],'array',array());
					if(is_array($groups) && !empty($groups)) {
						$criteria['groups'] = array();
						
						foreach($groups as $group_id) {
							@$all = DevblocksPlatform::importGPC($_REQUEST['value_group'.$group_id.'_all'],'integer',0);
							
							// Did we only want to watch specific buckets in this group?
							$bucket_ids = array();
							if(!$all)
								@$bucket_ids = DevblocksPlatform::importGPC($_REQUEST['value_group'.$group_id.'_buckets'],'array',array());
							
							// Add to criteria (key=group id, val=array of bucket ids)
							$criteria['groups'][$group_id] = $bucket_ids;
						}					
					}
					unset($criteria['value']);
					break;
				case 'next_worker_id':
					break;
				case 'subject':
					break;
				case 'from':
					break;
//				case 'tocc':
//					break;
				case 'header1':
				case 'header2':
				case 'header3':
				case 'header4':
				case 'header5':
					if(null != (@$header = DevblocksPlatform::importGPC($_POST[$rule],'string',null)))
						$criteria['header'] = strtolower($header);
					break;
				case 'body':
					break;
				default: // ignore invalids // [TODO] Very redundant
					// Custom fields
					if("cf_" == substr($rule,0,3)) {
						$field_id = intval(substr($rule,3));
						
						if(!isset($custom_fields[$field_id]))
							continue;

						// [TODO] Operators
							
						switch($custom_fields[$field_id]->type) {
							case 'S': // string
							case 'T': // clob
							case 'U': // URL
								@$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','regexp');
								$criteria['oper'] = $oper;
								break;
							case 'D': // dropdown
							case 'M': // multi-dropdown
							case 'X': // multi-checkbox
							case 'W': // worker
								@$in_array = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id],'array',array());
								$out_array = array();
								
								// Hash key on the option for quick lookup later
								if(is_array($in_array))
								foreach($in_array as $k => $v) {
									$out_array[$v] = $v;
								}
								
								$criteria['value'] = $out_array;
								break;
							case 'E': // date
								@$from = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_from'],'string','0');
								@$to = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_to'],'string','now');
								$criteria['from'] = $from;
								$criteria['to'] = $to;
								unset($criteria['value']);
								break;
							case 'N': // number
								@$oper = DevblocksPlatform::importGPC($_REQUEST['value_cf_'.$field_id.'_oper'],'string','=');
								$criteria['oper'] = $oper;
								$criteria['value'] = intval($value);
								break;
							case 'C': // checkbox
								$criteria['value'] = intval($value);
								break;
						}
						
					} else {
						continue;
					}
					
					break;
			}
			
			$criterion[$rule] = $criteria;
		}
		
		// Actions
		if(is_array($do))
		foreach($do as $act) {
			$action = array();
			
			switch($act) {
				// Forward a copy to...
				case 'email':
					@$emails = DevblocksPlatform::importGPC($_REQUEST['do_email'],'array',array());
					if(!empty($emails)) {
						$action = array(
							'to' => $emails
						);
					}
					break;
					
				// Watcher notification
				case 'notify':
					//@$emails = DevblocksPlatform::importGPC($_REQUEST['do_email'],'array',array());
					//if(!empty($emails)) {
						$action = array(
							//'to' => $emails
						);
					//}
					break;
			}
			
			$actions[$act] = $action;
		}

   		$fields = array(
   			DAO_WatcherMailFilter::NAME => $name,
   			DAO_WatcherMailFilter::IS_DISABLED => $is_disabled,
   			DAO_WatcherMailFilter::WORKER_ID => $worker_id,
   			DAO_WatcherMailFilter::CRITERIA_SER => serialize($criterion),
   			DAO_WatcherMailFilter::ACTIONS_SER => serialize($actions),
   		);

   		// Create
   		if(empty($id)) {
   			$fields[DAO_WatcherMailFilter::POS] = 0;
	   		$id = DAO_WatcherMailFilter::create($fields);
	   		
	   	// Update
   		} else {
   			DAO_WatcherMailFilter::update($id, $fields);
   		}
   		
		exit;
   		//DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences','watchers')));
	}
	
	function getWorkerAddressesAction() {
   		@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
	
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		$addresses = DAO_AddressToWorker::getByWorker($worker_id);
		$tpl->assign('addresses', $addresses);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'preferences/worker_addresses.tpl');
	}
	
};

class C4_WatcherView extends C4_AbstractView {
	const DEFAULT_ID = 'watchers';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Watchers';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_WatcherMailFilter::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_WatcherMailFilter::CREATED,
			SearchFields_WatcherMailFilter::WORKER_ID,
			SearchFields_WatcherMailFilter::POS,
		);

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_WatcherMailFilter::search(
			array(),
			$this->params,
			$this->renderLimit,
			$this->renderPage,
			$this->renderSortBy,
			$this->renderSortAsc
		);
		
		return $objects;
	}

	function render() {
		$this->_sanitize();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);
		$tpl->assign('view', $this);

		// Workers
		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);
		
		$tpl->cache_lifetime = "0";
		$tpl->assign('view_fields', $this->getColumns());
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.watchers/templates/config/watchers/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_WatcherMailFilter::NAME:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_WatcherMailFilter::ID:
			case SearchFields_WatcherMailFilter::POS:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
			case SearchFields_WatcherMailFilter::CREATED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
			case SearchFields_WatcherMailFilter::IS_DISABLED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_WatcherMailFilter::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$tpl->assign('workers', $workers);
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__worker.tpl');
				break;
			default:
				echo '';
				break;
		}
	}

	function renderCriteriaParam($param) {
		$field = $param->field;
		$values = !is_array($param->value) ? array($param->value) : $param->value;

		switch($field) {
			case SearchFields_WatcherMailFilter::WORKER_ID:
				$workers = DAO_Worker::getAll();
				$strings = array();

				foreach($values as $val) {
					if(0==$val) {
						$strings[] = "Nobody";
					} else {
						if(!isset($workers[$val]))
							continue;
						$strings[] = $workers[$val]->getName();
					}
				}
				echo implode(", ", $strings);
				break;
			
			default:
				parent::renderCriteriaParam($param);
				break;
		}
	}

	static function getFields() {
		return SearchFields_WatcherMailFilter::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_WatcherMailFilter::ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_WatcherMailFilter::ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
//			SearchFields_WatcherMailFilter::LOG_DATE => new DevblocksSearchCriteria(SearchFields_WatcherMailFilter::LOG_DATE,DevblocksSearchCriteria::OPER_BETWEEN,array('-1 month','now')),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_WatcherMailFilter::NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_WatcherMailFilter::ID:
			case SearchFields_WatcherMailFilter::POS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			case SearchFields_WatcherMailFilter::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
			case SearchFields_WatcherMailFilter::IS_DISABLED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			case SearchFields_WatcherMailFilter::CREATED:
				@$from = DevblocksPlatform::importGPC($_REQUEST['from'],'string','');
				@$to = DevblocksPlatform::importGPC($_REQUEST['to'],'string','');

				if(empty($from)) $from = 0;
				if(empty($to)) $to = 'today';

				$criteria = new DevblocksSearchCriteria($field,$oper,array($from,$to));
				break;
		}

		if(!empty($criteria)) {
			$this->params[$field] = $criteria;
			$this->renderPage = 0;
		}
	}
	
	function doBulkUpdate($filter, $do, $ids=array()) {
		@set_time_limit(0);
	  
		$change_fields = array();
		$custom_fields = array();
		$do_delete = false;

		// Make sure we have actions
		if(empty($do))
			return;

		// Make sure we have checked items if we want a checked list
		if(0 == strcasecmp($filter,"checks") && empty($ids))
			return;
			
		if(is_array($do))
		foreach($do as $k => $v) {
			switch($k) {
				case 'status':
					if(2==$v) {
						$do_delete = true;
					} else {
						$change_fields[DAO_WatcherMailFilter::IS_DISABLED] = (!empty($v)?1:0);
					}
					break;
				default:
					// Custom fields
//					if(substr($k,0,3)=="cf_") {
//						$custom_fields[substr($k,3)] = $v;
//					}
					break;
			}
		}

		$pg = 0;

		if(empty($ids))
		do {
			list($objects,$null) = DAO_WatcherMailFilter::search(
				array(),
				$this->params,
				100,
				$pg++,
				SearchFields_WatcherMailFilter::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if($do_delete) {
				DAO_WatcherMailFilter::delete($batch_ids);
				
			} else {
				DAO_WatcherMailFilter::update($batch_ids, $change_fields);

				// Custom Fields
				//self::_doBulkSetCustomFields(ChCustomFieldSource_TimeEntry::ID, $custom_fields, $batch_ids);
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}
		
};

class DAO_WatcherMailFilter extends DevblocksORMHelper {
	const ID = 'id';
	const POS = 'pos';
	const NAME = 'name';
	const CREATED = 'created';
	const IS_DISABLED = 'is_disabled';
	const WORKER_ID = 'worker_id';
	const CRITERIA_SER = 'criteria_ser';
	const ACTIONS_SER = 'actions_ser';

	static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO watcher_mail_filter (id,created) ".
			"VALUES (%d,%d)",
			$id,
			time()
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'watcher_mail_filter', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_WatcherMailFilter[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, pos, name, created, is_disabled, worker_id, criteria_ser, actions_ser ".
			"FROM watcher_mail_filter ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY pos DESC";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_WatcherMailFilter	 */
	static function get($id) {
		$objects = self::getWhere(sprintf("%s = %d",
			self::ID,
			$id
		));
		
		if(isset($objects[$id]))
			return $objects[$id];
		
		return null;
	}
	
	/**
	 * @param ADORecordSet $rs
	 * @return Model_WatcherMailFilter[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_WatcherMailFilter();
			$object->id = $rs->fields['id'];
			$object->pos = $rs->fields['pos'];
			$object->name = $rs->fields['name'];
			$object->created = $rs->fields['created'];
			$object->is_disabled = intval($rs->fields['is_disabled']);
			$object->worker_id = intval($rs->fields['worker_id']);
			
			if(null != (@$criteria_ser = $rs->fields['criteria_ser']))
				if(false === (@$object->criteria = unserialize($criteria_ser)))
					$object->criteria = array();

			if(null != (@$actions_ser = $rs->fields['actions_ser']))
				if(false === ($object->actions = unserialize($actions_ser)))
					$object->actions = array();
			
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
	
	public static function increment($id) {
		$db = DevblocksPlatform::getDatabaseService();
		$db->Execute(sprintf("UPDATE watcher_mail_filter SET pos = pos + 1 WHERE id = %d",
			$id
		));
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM watcher_mail_filter WHERE id IN (%s)", $ids_list));
		
		return true;
	}

	private static function _deleteWhere($where) {
		if(empty($where))
			return FALSE;
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM watcher_mail_filter WHERE %s", $where);
		$db->Execute($sql);
	}


	public static function deleteByWorkerIds($ids) {
		if(!is_array($ids)) $ids = array($ids);
		
		self::_deleteWhere(sprintf("%s IN (%s)",
			self::WORKER_ID,
			implode(',', $ids)
		));
	}
	
	public static function deleteByGroupIds($ids) {
		if(!is_array($ids)) $ids = array($ids);

		// [TODO] use cache
		$filters = self::getWhere();
		foreach($filters as $filter_id => $filter) {
			if(!isset($filter->criteria['groups']))
				continue;
				
			// If we're using the group being nuked...
			$changed = false;
			foreach($ids as $group_id) {
				if(isset($filter->criteria['groups']['groups'][$group_id])) {
					unset($filter->criteria['groups']['groups'][$group_id]);
					$changed = true;
				}
			}
			
			// If we changed the criteria of a filter, save it
			if($changed) {
				$fields = array(
					DAO_WatcherMailFilter::CRITERIA_SER => serialize($filter->criteria),
				);
				DAO_WatcherMailFilter::update($filter->id, $fields);
			}
		}
		
		// [TODO] invalidate cache
	}

	public static function deleteByBucketIds($ids) {
		if(!is_array($ids)) $ids = array($ids);

		// [TODO] use cache
		$filters = self::getWhere();
		foreach($filters as $filter_id => $filter) {
			if(!isset($filter->criteria['groups']['groups']))
				continue;	
			
			// If we're using the bucket being nuked...
			$changed = false;
			foreach($filter->criteria['groups']['groups'] as $group_id => $buckets) {
				foreach($ids as $bucket_id) {
					if(false !== ($pos = array_search($bucket_id, $buckets))) {
						unset($filter->criteria['groups']['groups'][$group_id][$pos]);
						$changed = true;
					}
				}
			}
			
			if($changed) {
				$fields = array(
					DAO_WatcherMailFilter::CRITERIA_SER => serialize($filter->criteria),
				);
				DAO_WatcherMailFilter::update($filter->id, $fields);
			}
		}
		
		// [TODO] invalidate cache
	}
	
    /**
     * Enter description here...
     *
     * @param DevblocksSearchCriteria[] $params
     * @param integer $limit
     * @param integer $page
     * @param string $sortBy
     * @param boolean $sortAsc
     * @param boolean $withCounts
     * @return array
     */
    static function search($columns, $params, $limit=10, $page=0, $sortBy=null, $sortAsc=null, $withCounts=true) {
		$db = DevblocksPlatform::getDatabaseService();
		$fields = SearchFields_WatcherMailFilter::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"wmf.id as %s, ".
			"wmf.pos as %s, ".
			"wmf.name as %s, ".
			"wmf.created as %s, ".
			"wmf.is_disabled as %s, ".
			"wmf.worker_id as %s ",
			    SearchFields_WatcherMailFilter::ID,
			    SearchFields_WatcherMailFilter::POS,
			    SearchFields_WatcherMailFilter::NAME,
			    SearchFields_WatcherMailFilter::CREATED,
			    SearchFields_WatcherMailFilter::IS_DISABLED,
			    SearchFields_WatcherMailFilter::WORKER_ID
			 );
		
		$join_sql = 
			"FROM watcher_mail_filter wmf "
		;
			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=tt.debit_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").

		// Custom field joins
//		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
//			$tables,
//			$params,
//			'wmf.id',
//			$select_sql,
//			$join_sql
//		);
		
		$where_sql = "".
			(!empty($wheres) ? sprintf("WHERE %s ",implode(' AND ',$wheres)) : "");
			
		$sort_sql = (!empty($sortBy) ? sprintf("ORDER BY %s %s ",$sortBy,($sortAsc || is_null($sortAsc))?"ASC":"DESC") : " ");
		
		$sql = 
			$select_sql.
			$join_sql.
			$where_sql.
			//($has_multiple_values ? 'GROUP BY wmf.id ' : '').
			$sort_sql;

		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_WatcherMailFilter::ID]);
			$results[$id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT wmf.id) " : "SELECT COUNT(wmf.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		return array($results,$total);
    }	
	
};

class SearchFields_WatcherMailFilter {
	// Watcher_Mail_Filter
	const ID = 'wmf_id';
	const POS = 'wmf_pos';
	const NAME = 'wmf_name';
	const CREATED = 'wmf_created';
	const WORKER_ID = 'wmf_worker_id';
	const IS_DISABLED = 'wmf_is_disabled';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'wmf', 'id', null, ucwords($translate->_('common.id'))),
			self::POS => new DevblocksSearchField(self::POS, 'wmf', 'pos', null, ucwords($translate->_('watcher.filter.model.hits'))),
			self::NAME => new DevblocksSearchField(self::NAME, 'wmf', 'name', null, ucwords($translate->_('common.name'))),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'wmf', 'created', null, ucwords($translate->_('common.created'))),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'wmf', 'worker_id', null, ucwords($translate->_('common.worker'))),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'wmf', 'is_disabled', null, ucwords($translate->_('common.disabled'))),
		);
		
		// Custom Fields
//		$fields = DAO_CustomField::getBySource(ChCustomFieldSource_TimeEntry::ID);
//		if(is_array($fields))
//		foreach($fields as $field_id => $field) {
//			$key = 'cf_'.$field_id;
//			$columns[$key] = new DevblocksSearchField($key,$key,'field_value',null,$field->name);
//		}
		
		// Sort by label (translation-conscious)
		uasort($columns, create_function('$a, $b', "return strcasecmp(\$a->db_label,\$b->db_label);\n"));
		
		return $columns;
	}
};

class Model_WatcherMailFilter {
	public $id;
	public $pos;
	public $name;
	public $created;
	public $is_disabled=0;
	public $worker_id;
	public $criteria;
	public $actions;
	
	/**
	 * @return Model_WatcherMailFilter[]|false
	 */
	static function getMatches(CerberusTicket $ticket, $event, $only_worker_id=null) {
		$matches = array();
		
		if(!empty($only_worker_id)) {
			$filters = DAO_WatcherMailFilter::getWhere(sprintf("%s = %d AND %s = %d",
				DAO_WatcherMailFilter::WORKER_ID,
				$only_worker_id,
				DAO_WatcherMailFilter::IS_DISABLED,
				0
			));
		} else {
			$filters = DAO_WatcherMailFilter::getWhere(sprintf("%s = %d",
				DAO_WatcherMailFilter::IS_DISABLED,
				0
			));
		}

		// [JAS]: Don't send obvious spam to watchers.
		if($ticket->spam_score >= 0.9000)
			return false;
			
		// Build our objects
		$ticket_from = DAO_Address::get($ticket->last_wrote_address_id);
		$ticket_group_id = $ticket->team_id;
		
		// [TODO] These expensive checks should only populate when needed
		$messages = DAO_Ticket::getMessagesByTicket($ticket->id);
		$message_headers = array();

		if(empty($messages))
			return false;
		
		if(null != (@$message_last = array_pop($messages))) { /* @var $message_last CerberusMessage */
			$message_headers = $message_last->getHeaders();
		}

		// Clear the rest of the message manifests
		unset($messages);
		
		$custom_fields = DAO_CustomField::getAll();
		
		// Lazy load when needed on criteria basis
		$ticket_field_values = null;
		$address_field_values = null;
		$org_field_values = null;
		
		// Worker memberships (for checking permissions)
		$workers = DAO_Worker::getAll();
		$group_rosters = DAO_Group::getRosters();
		
		// Check filters
		if(is_array($filters))
		foreach($filters as $filter) { /* @var $filter Model_WatcherMailFilter */
			$passed = 0;

			// check the worker's group memberships
			if(!isset($workers[$filter->worker_id]) // worker doesn't exist 
				|| $workers[$filter->worker_id]->is_disabled // is disabled
				|| (!$workers[$filter->worker_id]->is_superuser  // not a superuser, and...
					&& !isset($group_rosters[$ticket->team_id][$filter->worker_id]))) { // no membership
				continue;
			}

			// check criteria
			foreach($filter->criteria as $rule_key => $rule) {
				@$value = $rule['value'];
							
				switch($rule_key) {
					case 'dayofweek':
						$current_day = strftime('%w');
						//$current_day = 1;

						// Forced to English abbrevs as indexes
						$days = array('sun','mon','tue','wed','thu','fri','sat');
						
						// Is the current day enabled?
						if(isset($rule[$days[$current_day]])) {
							$passed++;
						}
							
						break;
						
					case 'timeofday':
						$current_hour = strftime('%H');
						$current_min = strftime('%M');
						//$current_hour = 17;
						//$current_min = 5;

						if(null != ($from_time = @$rule['from']))
							list($from_hour, $from_min) = explode(':', $from_time);
						
						if(null != ($to_time = @$rule['to']))
							if(list($to_hour, $to_min) = explode(':', $to_time));

						// Do we need to wrap around to the next day's hours?
						if($from_hour > $to_hour) { // yes
							$to_hour += 24; // add 24 hrs to the destination (1am = 25th hour)
						}
							
						// Are we in the right 24 hourly range?
						if((integer)$current_hour >= $from_hour && (integer)$current_hour <= $to_hour) {
							// If we're in the first hour, are we minutes early?
							if($current_hour==$from_hour && (integer)$current_min < $from_min)
								break;
							// If we're in the last hour, are we minutes late?
							if($current_hour==$to_hour && (integer)$current_min > $to_min)
								break;
								
							$passed++;
						}
						break;
						
					case 'event': 
						if(!empty($event) && is_array($rule) && isset($rule[$event]))
							$passed++;
						break;					
						
					case 'groups':
						if(null !== (@$group_buckets = $rule['groups'][$ticket->team_id]) // group is set
							&& (empty($group_buckets) || in_array($ticket->category_id,$group_buckets)))
								$passed++;
						break;
						
					case 'next_worker_id':
						// If it's an assigned event, we only care about the filter's owner
						if(!empty($event) && 0==strcasecmp($event,'ticket_assignment')) {
							if(intval($value)==intval($filter->worker_id)) {
								$passed++;
								break;
							}
						}

						if(intval($value)==intval($ticket->next_worker_id))
							$passed++;
						break;					

					case 'mask':
						$regexp_mask = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_mask, $ticket->mask)) {
							$passed++;
						}
						break;
						
					case 'from':
						$regexp_from = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_from, $ticket_from->email)) {
							$passed++;
						}
						break;
						
					case 'subject':
						$regexp_subject = DevblocksPlatform::strToRegExp($value);
						if(@preg_match($regexp_subject, $ticket->subject)) {
							$passed++;
						}
						break;
						
					case 'body':
						if(null == ($message_body = $message_last->getContent()))
							break;
							
						// Line-by-line body scanning (sed-like)
						$lines = preg_split("/[\r\n]/", $message_body);
						if(is_array($lines))
						foreach($lines as $line) {
							if(@preg_match($value, $line)) {
								$passed++;
								break;
							}
						}
						break;
						
					case 'header1':
					case 'header2':
					case 'header3':
					case 'header4':
					case 'header5':
						@$header = strtolower($rule['header']);

						if(empty($header)) {
							$passed++;
							break;
						}
						
						if(empty($value)) { // we're checking for null/blanks
							if(!isset($message_headers[$header]) || empty($message_headers[$header])) {
								$passed++;
							}
							
						} elseif(isset($message_headers[$header]) && !empty($message_headers[$header])) {
							$regexp_header = DevblocksPlatform::strToRegExp($value);
							
							// Flatten CRLF
							if(@preg_match($regexp_header, str_replace(array("\r","\n"),' ',$message_headers[$header]))) {
								$passed++;
							}
						}
						
						break;
						
					default: // ignore invalids
						// Custom Fields
						if(0==strcasecmp('cf_',substr($rule_key,0,3))) {
							$field_id = substr($rule_key,3);

							// Make sure it exists
							if(null == (@$field = $custom_fields[$field_id]))
								continue;

							// Lazy values loader
							$field_values = array();
							switch($field->source_extension) {
								case ChCustomFieldSource_Address::ID:
									if(null == $address_field_values)
										$address_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Address::ID, $ticket_from->id));
									$field_values =& $address_field_values;
									break;
								case ChCustomFieldSource_Org::ID:
									if(null == $org_field_values)
										$org_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Org::ID, $ticket_from->contact_org_id));
									$field_values =& $org_field_values;
									break;
								case ChCustomFieldSource_Ticket::ID:
									if(null == $ticket_field_values)
										$ticket_field_values = array_shift(DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $ticket->id));
									$field_values =& $ticket_field_values;
									break;
							}
							
							// Type sensitive value comparisons
							// [TODO] Operators
							switch($field->type) {
								case 'S': // string
								case 'T': // clob
								case 'U': // URL
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : '';
									$oper = isset($rule['oper']) ? $rule['oper'] : "=";
									
									if($oper == "=" && @preg_match(DevblocksPlatform::strToRegExp($value, true), $field_val))
										$passed++;
									elseif($oper == "!=" && @!preg_match(DevblocksPlatform::strToRegExp($value, true), $field_val))
										$passed++;
									break;
								case 'N': // number
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									$oper = isset($rule['oper']) ? $rule['oper'] : "=";
									
									if($oper=="=" && intval($field_val)==intval($value))
										$passed++;
									elseif($oper=="!=" && intval($field_val)!=intval($value))
										$passed++;
									elseif($oper==">" && intval($field_val) > intval($value))
										$passed++;
									elseif($oper=="<" && intval($field_val) < intval($value))
										$passed++;
									break;
								case 'E': // date
									$field_val = isset($field_values[$field_id]) ? intval($field_values[$field_id]) : 0;
									$from = isset($rule['from']) ? $rule['from'] : "0";
									$to = isset($rule['to']) ? $rule['to'] : "now";
									
									if(intval(@strtotime($from)) <= $field_val && intval(@strtotime($to)) >= $field_val) {
										$passed++;
									}
									break;
								case 'C': // checkbox
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : 0;
									if(intval($value)==intval($field_val))
										$passed++;
									break;
								case 'D': // dropdown
								case 'X': // multi-checkbox
								case 'M': // multi-picklist
								case 'W': // worker
									$field_val = isset($field_values[$field_id]) ? $field_values[$field_id] : array();
									if(!is_array($value)) $value = array($value);
										
									if(is_array($field_val)) { // if multiple things set
										foreach($field_val as $v) { // loop through possible
											if(isset($value[$v])) { // is any possible set?
												$passed++;
												break;
											}
										}
										
									} else { // single
										if(isset($value[$field_val])) { // is our set field in possibles?
											$passed++;
											break;
										}
										
									}
									break;
							}
						}
						break;
				}
			}
			
			// If our rule matched every criteria, stop and return the filter
			if($passed == count($filter->criteria)) {
				DAO_WatcherMailFilter::increment($filter->id); // ++ the times we've matched
				$matches[$filter->id] = $filter;
			}
		}
		
		if(!empty($matches))
			return $matches;
		
		// No matches
		return false;
	}
};
