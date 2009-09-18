<?php

class Model_AnswernetAuditLog {
  public $enabled_comments = '';
  public $update_comment = '';
};

class AnswernetLastActionAndAuditLogConfigTab extends Extension_ConfigTab {
	const ID = 'answernet.last_action_and_audit_log.config.tab';

	function showTab() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$tpl->cache_lifetime = "0";

    $enabled_comments = intval($this->getParam('enabled_comments', 0));
    $tpl->assign('enabled_comments', $enabled_comments);

    $update_comment = intval($this->getParam('update_comment', 0));
    $tpl->assign('update_comment', $update_comment);
    print_r($this->getParams());

    @$address = DAO_Address::get($address_id);
    @$worker_id = DAO_Worker::lookupAgentEmail($address->email);
		$tpl->display('file:' . $tpl_path . 'config.tpl');
	}

  function getSettings() {
    return self::getParams();
  }

	function saveAnswernetAction() {
    $tpl = DevblocksPlatform::getTemplateService();
    $tpl_path = dirname(dirname(__FILE__)) . '/templates/';
    $tpl->cache_lifetime = "0";

    @$enabled_comments = DevblocksPlatform::importGPC($_REQUEST['enabled_comments'],'integer',0);
    @$update_comment = DevblocksPlatform::importGPC($_POST['update_comment'],'integer',0);
    $this->setParam('enabled_comments', $enabled_comments);
    $this->setParam('update_comment', $update_comment);
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
            	break;
        }
    }

  function newTicketComment($event) {
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

    print_r(AnswernetLastActionAndAuditLogConfigTab::getSettings());
    if (class_exists('DAO_TicketAuditLog',true)):
//      if(intval($this->getParam('enabled_comments', 0))) {
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
//      }
    endif;
  }


//    private function _bucketDeleted($event) {
//    	@$bucket_ids = $event->params['bucket_ids'];
//    	DAO_WatcherMailFilter::deleteByBucketIds($bucket_ids);
//    }

};
