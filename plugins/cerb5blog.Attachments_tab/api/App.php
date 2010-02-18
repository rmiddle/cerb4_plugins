<?php
 
class Cerb5BlogAttachementsTicketTab extends Extension_TicketTab {
	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}
 
	function showTab() {
		@$ticket_id = DevblocksPlatform::importGPC($_REQUEST['ticket_id'],'integer',0);
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		
//		$tpl->assign('response_uri', 'config/attachments');

		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'Ticket_View_Attachments';
		$defaults->id = C4_AttachmentView::DEFAULT_ID;

		$view_attachements = C4_AbstractViewLoader::getView(C4_AttachmentView::DEFAULT_ID, $defaults);
		$view_attachements->params = array(
			SearchFields_Attachment::TICKET_ID => new DevblocksSearchCriteria(SearchFields_Attachment::TICKET_ID,DevblocksSearchCriteria::OPER_EQ,$ticket_id)
		);
		
		$tpl->assign('view_attachements', $view_attachements);
//		$tpl->assign('view_fields', C4_AttachmentView::getFields());
//		$tpl->assign('view_searchable_fields', C4_AttachmentView::getSearchFields());
		
		$tpl->display('file:' . $tpl_path . 'attachments/index.tpl');
	}

	function saveTab() {
	}
};