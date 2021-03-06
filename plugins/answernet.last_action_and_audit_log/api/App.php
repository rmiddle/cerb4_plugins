<?php

class AnswernetLastActionAndAuditLogConfigTab extends Extension_ConfigTab {
	const ID = 'answernet.last_action_and_audit_log.config.tab';
  const AL_COMMENT_ENABLED = 'al_comment_enabled';
  const UF_COMMENT_ENABLED = 'uf_comment_enabled';
  const AL_MERGE_ENABLED = 'al_merge_enabled';
  const UF_MERGE_ENABLED = 'uf_merge_enabled';

  function __construct($manifest) {
      parent::__construct($manifest);
  }

	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->cache_lifetime = "0";

    $al_comment_enabled = intval($this->getParam('al_comment_enabled', 0));
    $tpl->assign('al_comment_enabled', $al_comment_enabled);

    $uf_comment_enabled = intval($this->getParam('uf_comment_enabled', 0));
    $tpl->assign('uf_comment_enabled', $uf_comment_enabled);

    $al_merge_enabled = intval($this->getParam('al_merge_enabled', 0));
    $tpl->assign('al_merge_enabled', $al_merge_enabled);

    $uf_merge_enabled = intval($this->getParam('uf_merge_enabled', 0));
    $tpl->assign('uf_merge_enabled', $uf_merge_enabled);

    @$address = DAO_Address::get($address_id);
    @$worker_id = DAO_Worker::lookupAgentEmail($address->email);
		$tpl->display('file:' . $tpl_path . 'config.tpl');
	}

	function saveAnswernetAction() {
    $tpl = DevblocksPlatform::getTemplateService();
    $tpl_path = dirname(dirname(__FILE__)) . '/templates/';
    $tpl->cache_lifetime = "0";

    @$al_comment_enabled = DevblocksPlatform::importGPC($_REQUEST['al_comment_enabled'],'integer',0);
    @$uf_comment_enabled = DevblocksPlatform::importGPC($_POST['uf_comment_enabled'],'integer',0);
    @$al_merge_enabled = DevblocksPlatform::importGPC($_REQUEST['al_merge_enabled'],'integer',0);
    @$uf_merge_enabled = DevblocksPlatform::importGPC($_POST['uf_merge_enabled'],'integer',0);

    $this->setParam('al_comment_enabled', $al_comment_enabled);
    $this->setParam('uf_comment_enabled', $uf_comment_enabled);
    $this->setParam('al_merge_enabled', $al_merge_enabled);
    $this->setParam('uf_merge_enabled', $uf_merge_enabled);

    $tpl->display('file:' . $tpl_path . 'config_success.tpl');
	}
};

class AnswernetLastActionAndAuditLogEventListener extends DevblocksEventListenerExtension {
    const ID = 'answernet.last_action_and_audit_log.listeners';
    function __construct($manifest) {
        parent::__construct($manifest);
    }

    /**
     * @param Model_DevblocksEvent $event
     */
    function handleEvent(Model_DevblocksEvent $event) {
        switch($event->id) {
            case 'ticket.comment.create':
              $this->newTicketComment($event);
              break;

            case 'ticket.property.pre_change':
            	break;

            case 'ticket.reply.inbound':
            	break;

            case 'ticket.reply.outbound':
            	break;

            case 'ticket.merge':
              $this->mergeTicket($event);
            	break;
        }
    }

  private function newTicketComment($event) {
    DevblocksPlatform::getExtensions('cerberusweb.ticket.tab', true);
    // ticket_comment.id
    @$comment_id = $event->params['comment_id'];
    // ticket.id
    @$ticket_id = $event->params['ticket_id'];
    // address.id
    @$address_id = $event->params['address_id'];
    // text of actual comment.
    @$comment_text = $event->params['comment'];

    if(empty($ticket_id) || empty($address_id) || empty($comment_text))
      return;

    $setting_manifest = DevblocksPlatform::getExtension(AnswernetLastActionAndAuditLogConfigTab::ID);
    $setting = $setting_manifest->createInstance(); /* @var $job CerberusCronPageExtension */
    $al_comment_enabled = intval($setting->getParam(AnswernetLastActionAndAuditLogConfigTab::AL_COMMENT_ENABLED, 0));
    $uf_comment_enabled = intval($setting->getParam(AnswernetLastActionAndAuditLogConfigTab::UF_COMMENT_ENABLED, 0));

    if (class_exists('DAO_TicketAuditLog',true)):
      if($al_comment_enabled) {
        @$address = DAO_Address::get($address_id);
        @$worker_id = DAO_Worker::lookupAgentEmail($address->email);
        $fields = array(
          DAO_TicketAuditLog::TICKET_ID => $ticket_id,
          DAO_TicketAuditLog::WORKER_ID => $worker_id,
          DAO_TicketAuditLog::CHANGE_DATE => time(),
          DAO_TicketAuditLog::CHANGE_FIELD => "answernet.last_action_and_audit_log.type.comment",
          DAO_TicketAuditLog::CHANGE_VALUE => substr($comment_text,0,128),
        );
        $log_id = DAO_TicketAuditLog::create($fields);
        unset($fields);
      }
    endif;


    if($uf_comment_enabled) {
      $change_fields[DAO_Ticket::UPDATED_DATE] = time();
      DAO_Ticket::updateTicket($ticket_id, $change_fields);
      unset($change_fields);
    }
  }


  private function mergeTicket($event) {
    // Listen for ticket merges and update our internal ticket_id records

    @$new_ticket_id = $event->params['new_ticket_id'];
    @$old_ticket_ids = $event->params['old_ticket_ids'];

    $translate = DevblocksPlatform::getTranslationService();

    if(empty($new_ticket_id) || empty($old_ticket_ids))
      return;

    $setting_manifest = DevblocksPlatform::getExtension(AnswernetLastActionAndAuditLogConfigTab::ID);
    $setting = $setting_manifest->createInstance();
    $al_merge_enabled = intval($setting->getParam(AnswernetLastActionAndAuditLogConfigTab::AL_MERGE_ENABLED, 0));
    $uf_merge_enabled = intval($setting->getParam(AnswernetLastActionAndAuditLogConfigTab::UF_MERGE_ENABLED, 0));

    if(!($al_merge_enabled || $uf_merge_enabled))
      return;

    $active_worker = CerberusApplication::getActiveWorker();
    $worker_id = $active_worker->id;

    if (class_exists('DAO_TicketAuditLog',true)):
      if($al_merge_enabled) {
        foreach($old_ticket_ids as $old_id) {
          $old_ticket = DAO_Ticket::getTicket($old_id);
          $translate_str = $translate->_('answernet.last_action_and_audit_log.post.merge.new_ticket');
          $translated = sprintf($translate_str,$old_id, $old_ticket->mask);

          $fields = array(
            DAO_TicketAuditLog::TICKET_ID => $new_ticket_id,
            DAO_TicketAuditLog::WORKER_ID => $worker_id,
            DAO_TicketAuditLog::CHANGE_DATE => time(),
            DAO_TicketAuditLog::CHANGE_FIELD => "answernet.last_action_and_audit_log.type.merge",
            DAO_TicketAuditLog::CHANGE_VALUE => substr($translated,0,128),
          );
          $log_id = DAO_TicketAuditLog::create($fields);
        }
        unset($fields);
      }
    endif;

    if($uf_merge_enabled) {
      $new_change_fields[DAO_Ticket::UPDATED_DATE] = time();
      DAO_Ticket::updateTicket($new_ticket_id, $new_change_fields);
      unset($new_change_fields);
    }
  }

//    private function _bucketDeleted($event) {
//    	@$bucket_ids = $event->params['bucket_ids'];
//    	DAO_WatcherMailFilter::deleteByBucketIds($bucket_ids);
//    }

};
