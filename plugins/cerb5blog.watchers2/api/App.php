<?php
class Cerb5blogWatchers2ConfigTab extends Extension_ConfigTab {
	const ID = 'cerb5blog.watchers2.config.tab';
	
	function showTab() {
		$settings = CerberusSettings::getInstance();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl_path = dirname(dirname(__FILE__)) . '/templates/';
		$core_tplpath = dirname(dirname(dirname(__FILE__))) . '/cerberusweb.core/templates/';
		$tpl->assign('core_tplpath', $core_tplpath);
		$tpl->cache_lifetime = "0";

		$tpl->assign('response_uri', 'config/watchers2');
		
		$defaults = new C4_AbstractViewModel();
		$defaults->class_name = 'C4_Watcher2View';
		$defaults->id = C4_Watcher2View::DEFAULT_ID;
		$defaults->renderSortBy = SearchFields_Watcher2MailFilter::POS;
		$defaults->renderSortAsc = 0;
		
		$view = C4_AbstractViewLoader::getView(C4_Watcher2View::DEFAULT_ID, $defaults);
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_Watcher2View::getFields());
		$tpl->assign('view_searchable_fields', C4_Watcher2View::getSearchFields());
		
		$tpl->display('file:' . $tpl_path . 'config/watchers2/index.tpl');
	}
	
	function saveTab() {
		@$plugin_id = DevblocksPlatform::importGPC($_REQUEST['plugin_id'],'string');

		DevblocksPlatform::redirect(new DevblocksHttpResponse(array('config','watchers2')));
		exit;
	}
};

class Cerb5blogWatchers2Preferences extends Extension_PreferenceTab {
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
		
		$tpl->assign('response_uri', 'preferences/watchers2');
		
		if(null == ($view = C4_AbstractViewLoader::getView('prefs.watchers2'))) {
			$view = new C4_Watcher2View();
			$view->id = 'prefs.watchers2';
			$view->name = "My Watcher 2.0 Filters";
			$view->renderSortBy = SearchFields_Watcher2MailFilter::POS;
			$view->renderSortAsc = 0;
			$view->params = array(
				SearchFields_Watcher2MailFilter::WORKER_ID => new DevblocksSearchCriteria(SearchFields_Watcher2MailFilter::WORKER_ID,'eq',$worker->id),
			);
			
			C4_AbstractViewLoader::setView($view->id, $view);
		}
		
		$tpl->assign('view', $view);
		$tpl->assign('view_fields', C4_Watcher2View::getFields());
		$tpl->assign('view_searchable_fields', C4_Watcher2View::getSearchFields());
		
		$tpl->display('file:' . $this->_TPL_PATH . 'preferences/watchers2.tpl');
	}
    
	// Post
	function saveTab() {
	}
	
	// Ajax
	function showWatcher2BulkPanelAction() {
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
	function doWatcher2BulkPanelAction() {
		// Checked rows
	    @$ids_str = DevblocksPlatform::importGPC($_REQUEST['ids'],'string');
		$ids = DevblocksPlatform::parseCsvString($ids_str);

		// Filter: whole list or check
	    @$filter = DevblocksPlatform::importGPC($_REQUEST['filter'],'string','');
	    
	    // View
		@$view_id = DevblocksPlatform::importGPC($_REQUEST['view_id'],'string');
		$view = C4_AbstractViewLoader::getView($view_id);
		
		// Watcher2 fields
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
	function showWatcher2PanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->_TPL_PATH);

		$active_worker = CerberusApplication::getActiveWorker();
		
		if(null != ($filter = DAO_Watcher2MailFilter::get($id))) {
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
	
	function saveWatcher2PanelAction() {
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
			$name = $translate->_('watcher2.ui.pref.filters');
		
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
					if(in_array(0,$days)) $criteria['sun'] = $translate->_('watcher2.dow.sunday');
					if(in_array(1,$days)) $criteria['mon'] = $translate->_('watcher2.dow.monday');
					if(in_array(2,$days)) $criteria['tue'] = $translate->_('watcher2.dow.tuesday');
					if(in_array(3,$days)) $criteria['wed'] = $translate->_('watcher2.dow.wednesday');
					if(in_array(4,$days)) $criteria['thu'] = $translate->_('watcher2.dow.thursday');
					if(in_array(5,$days)) $criteria['fri'] = $translate->_('watcher2.dow.friday');
					if(in_array(6,$days)) $criteria['sat'] = $translate->_('watcher2.dow.saturday');
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
					
				// Watcher2 notification
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
   			DAO_Watcher2MailFilter::NAME => $name,
   			DAO_Watcher2MailFilter::IS_DISABLED => $is_disabled,
   			DAO_Watcher2MailFilter::WORKER_ID => $worker_id,
   			DAO_Watcher2MailFilter::CRITERIA_SER => serialize($criterion),
   			DAO_Watcher2MailFilter::ACTIONS_SER => serialize($actions),
   		);

   		// Create
   		if(empty($id)) {
   			$fields[DAO_Watcher2MailFilter::POS] = 0;
	   		$id = DAO_Watcher2MailFilter::create($fields);
	   		
	   	// Update
   		} else {
   			DAO_Watcher2MailFilter::update($id, $fields);
   		}
   		
		exit;
   		//DevblocksPlatform::redirect(new DevblocksHttpResponse(array('preferences','watchers2')));
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
	
	/*
		Type List.
		1 = Assigned
		2 = Comment
		3 = Inbound
		4 = Outbound
		5 = Moved
	*/
		// Ajax
	function showTemplatesPanelAction() {
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('template_type', $template_type);

		$tpl->assign('tpl_path', $this->_TPL_PATH);
		
		$where = null;
		if(empty($templates_list)) {
			$where = sprintf("%s = %d",
				DAO_Watcher2Template::TEMPLATE_TYPE,
				$type
			);
		} 
		
		$templates = DAO_Watcher2Template::getWhere($where);
		$tpl->assign('templates', $templates);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'watcher2_template/templates_panel.tpl');
	}
	
	// Ajax
	function showTemplateEditPanelAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$template_type = DevblocksPlatform::importGPC($_REQUEST['type'],'integer',0);
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('template_type', $template_type);
				
		$template = DAO_Watcher2Template::get($id);
		$tpl->assign('template', $template);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'watcher2_template/template_edit_panel.tpl');
	}
	
	// Ajax
	function saveWatcher2TemplateAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer',0);
		@$title = DevblocksPlatform::importGPC($_REQUEST['title'],'string','');
		@$description = DevblocksPlatform::importGPC($_REQUEST['description'],'string','');
		@$template_type = DevblocksPlatform::importGPC($_REQUEST['template_type'],'integer',0);
		@$subject = DevblocksPlatform::importGPC($_REQUEST['template_subject'],'string','');
		@$content = DevblocksPlatform::importGPC($_REQUEST['template_content'],'string','');
		@$delete = DevblocksPlatform::importGPC($_REQUEST['do_delete'],'integer',0);
		
		$worker = CerberusApplication::getActiveWorker();
		
		if(empty($delete)) {
			$fields = array(
				DAO_Watcher2Template::TITLE => $title,
				DAO_Watcher2Template::DESCRIPTION => $description,
				DAO_Watcher2Template::TEMPLATE_TYPE => $type,
				DAO_Watcher2Template::TEMPLATE_SUBJECT => $subject,
				DAO_Watcher2Template::TEMPLATE_CONTENT => $content,
				DAO_Watcher2Template::OWNER_ID => $worker->id,
			);
			
			if(empty($id)) { // new
				$id = DAO_Watcher2Template::create($fields);
				
			} else { // edit
				DAO_Watcher2Template::update($id, $fields);			
				
			}
			
		} else { // delete
			DAO_Watcher2Template::delete($id);
		}
		
	}
	
	// Ajax
	function getTemplateAction() {
		@$id = DevblocksPlatform::importGPC($_REQUEST['id'],'integer');
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');

		$template = DAO_Watcher2Template::get($id);
		echo $template->getRenderedContent($reply_id);
	}

	// Ajax
	function getTemplatesAction() {
		@$txt_name = DevblocksPlatform::importGPC($_REQUEST['txt_name'],'string','');
		@$reply_id = DevblocksPlatform::importGPC($_REQUEST['reply_id'],'integer');
		@$type = DevblocksPlatform::importGPC($_REQUEST['type'],'integer',0);
		
		$db = DevblocksPlatform::getDatabaseService();
		
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('path', $this->_TPL_PATH);
		
		$tpl->assign('reply_id', $reply_id);
		$tpl->assign('txt_name', $txt_name);
		
		$where = sprintf("%s = %d",
			DAO_Watcher2Template::TEMPLATE_TYPE,
			$type
		);
		
		$templates = DAO_Watcher2Template::getWhere($where);
		$tpl->assign('templates', $templates);
		
		$tpl->display('file:' . $this->_TPL_PATH . 'watcher2_template/template_results.tpl');
	} 
};

class C4_Watcher2View extends C4_AbstractView {
	const DEFAULT_ID = 'watchers2';

	function __construct() {
		$this->id = self::DEFAULT_ID;
		$this->name = 'Watchers 2.0';
		$this->renderLimit = 25;
		$this->renderSortBy = SearchFields_Watcher2MailFilter::ID;
		$this->renderSortAsc = true;

		$this->view_columns = array(
			SearchFields_Watcher2MailFilter::CREATED,
			SearchFields_Watcher2MailFilter::WORKER_ID,
			SearchFields_Watcher2MailFilter::POS,
		);

		$this->doResetCriteria();
	}

	function getData() {
		$objects = DAO_Watcher2MailFilter::search(
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
		$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerb5blog.watchers2/templates/config/watchers2/view.tpl');
	}

	function renderCriteria($field) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('id', $this->id);

		switch($field) {
			case SearchFields_Watcher2MailFilter::NAME:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__string.tpl');
				break;
			case SearchFields_Watcher2MailFilter::ID:
			case SearchFields_Watcher2MailFilter::POS:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__number.tpl');
				break;
			case SearchFields_Watcher2MailFilter::CREATED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__date.tpl');
				break;
			case SearchFields_Watcher2MailFilter::IS_DISABLED:
				$tpl->display('file:' . DEVBLOCKS_PLUGIN_PATH . 'cerberusweb.core/templates/internal/views/criteria/__bool.tpl');
				break;
			case SearchFields_Watcher2MailFilter::WORKER_ID:
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
			case SearchFields_Watcher2MailFilter::WORKER_ID:
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
		return SearchFields_Watcher2MailFilter::getFields();
	}

	static function getSearchFields() {
		$fields = self::getFields();
		unset($fields[SearchFields_Watcher2MailFilter::ID]);
		return $fields;
	}

	static function getColumns() {
		$fields = self::getFields();
		unset($fields[SearchFields_Watcher2MailFilter::ID]);
		return $fields;
	}

	function doResetCriteria() {
		parent::doResetCriteria();
		
		$this->params = array(
//			SearchFields_Watcher2MailFilter::LOG_DATE => new DevblocksSearchCriteria(SearchFields_Watcher2MailFilter::LOG_DATE,DevblocksSearchCriteria::OPER_BETWEEN,array('-1 month','now')),
		);
	}
	
	function doSetCriteria($field, $oper, $value) {
		$criteria = null;

		switch($field) {
			case SearchFields_Watcher2MailFilter::NAME:
				// force wildcards if none used on a LIKE
				if(($oper == DevblocksSearchCriteria::OPER_LIKE || $oper == DevblocksSearchCriteria::OPER_NOT_LIKE)
				&& false === (strpos($value,'*'))) {
					$value = '*'.$value.'*';
				}
				$criteria = new DevblocksSearchCriteria($field, $oper, $value);
				break;
			case SearchFields_Watcher2MailFilter::ID:
			case SearchFields_Watcher2MailFilter::POS:
				$criteria = new DevblocksSearchCriteria($field,$oper,$value);
				break;
			case SearchFields_Watcher2MailFilter::WORKER_ID:
				@$worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'array',array());
				$criteria = new DevblocksSearchCriteria($field,$oper,$worker_id);
				break;
			case SearchFields_Watcher2MailFilter::IS_DISABLED:
				@$bool = DevblocksPlatform::importGPC($_REQUEST['bool'],'integer',1);
				$criteria = new DevblocksSearchCriteria($field,$oper,$bool);
				break;
			case SearchFields_Watcher2MailFilter::CREATED:
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
						$change_fields[DAO_Watcher2MailFilter::IS_DISABLED] = (!empty($v)?1:0);
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
			list($objects,$null) = DAO_Watcher2MailFilter::search(
				array(),
				$this->params,
				100,
				$pg++,
				SearchFields_Watcher2MailFilter::ID,
				true,
				false
			);
			 
			$ids = array_merge($ids, array_keys($objects));
			 
		} while(!empty($objects));

		$batch_total = count($ids);
		for($x=0;$x<=$batch_total;$x+=100) {
			$batch_ids = array_slice($ids,$x,100);
			
			if($do_delete) {
				DAO_Watcher2MailFilter::delete($batch_ids);
				
			} else {
				DAO_Watcher2MailFilter::update($batch_ids, $change_fields);

				// Custom Fields
				//self::_doBulkSetCustomFields(ChCustomFieldSource_TimeEntry::ID, $custom_fields, $batch_ids);
			}
			
			unset($batch_ids);
		}

		unset($ids);
	}
		
};

class DAO_Watcher2MailFilter extends DevblocksORMHelper {
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
		
		$sql = sprintf("INSERT INTO cerb5blog_watchers2_filter (id,created) ".
			"VALUES (%d,%d)",
			$id,
			time()
		);
		$db->Execute($sql);
		
		self::update($id, $fields);
		
		return $id;
	}
	
	static function update($ids, $fields) {
		parent::_update($ids, 'cerb5blog_watchers2_filter', $fields);
	}
	
	/**
	 * @param string $where
	 * @return Model_Watcher2MailFilter[]
	 */
	static function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = "SELECT id, pos, name, created, is_disabled, worker_id, criteria_ser, actions_ser ".
			"FROM cerb5blog_watchers2_filter ".
			(!empty($where) ? sprintf("WHERE %s ",$where) : "").
			"ORDER BY pos DESC";
		$rs = $db->Execute($sql);
		
		return self::_getObjectsFromResult($rs);
	}

	/**
	 * @param integer $id
	 * @return Model_Watcher2MailFilter	 */
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
	 * @return Model_Watcher2MailFilter[]
	 */
	static private function _getObjectsFromResult($rs) {
		$objects = array();
		
		while(!$rs->EOF) {
			$object = new Model_Watcher2MailFilter();
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
		$db->Execute(sprintf("UPDATE cerb5blog_watchers2_filter SET pos = pos + 1 WHERE id = %d",
			$id
		));
	}
	
	static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$ids_list = implode(',', $ids);
		
		$db->Execute(sprintf("DELETE FROM cerb5blog_watchers2_filter WHERE id IN (%s)", $ids_list));
		
		return true;
	}

	private static function _deleteWhere($where) {
		if(empty($where))
			return FALSE;
			
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("DELETE FROM cerb5blog_watchers2_filter WHERE %s", $where);
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
					DAO_Watcher2MailFilter::CRITERIA_SER => serialize($filter->criteria),
				);
				DAO_Watcher2MailFilter::update($filter->id, $fields);
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
					DAO_Watcher2MailFilter::CRITERIA_SER => serialize($filter->criteria),
				);
				DAO_Watcher2MailFilter::update($filter->id, $fields);
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
		$fields = SearchFields_Watcher2MailFilter::getFields();
		
		// Sanitize
		if(!isset($fields[$sortBy]))
			$sortBy=null;

        list($tables,$wheres) = parent::_parseSearchParams($params, $columns, $fields,$sortBy);
		$start = ($page * $limit); // [JAS]: 1-based [TODO] clean up + document
		
		$select_sql = sprintf("SELECT ".
			"cwf.id as %s, ".
			"cwf.pos as %s, ".
			"cwf.name as %s, ".
			"cwf.created as %s, ".
			"cwf.is_disabled as %s, ".
			"cwf.worker_id as %s ",
			    SearchFields_Watcher2MailFilter::ID,
			    SearchFields_Watcher2MailFilter::POS,
			    SearchFields_Watcher2MailFilter::NAME,
			    SearchFields_Watcher2MailFilter::CREATED,
			    SearchFields_Watcher2MailFilter::IS_DISABLED,
			    SearchFields_Watcher2MailFilter::WORKER_ID
			 );
		
		$join_sql = 
			"FROM cerb5blog_watchers2_filter cwf "
		;
			// [JAS]: Dynamic table joins
//			(isset($tables['o']) ? "LEFT JOIN contact_org o ON (o.id=tt.debit_org_id)" : " ").
//			(isset($tables['mc']) ? "INNER JOIN message_content mc ON (mc.message_id=m.id)" : " ").

		// Custom field joins
//		list($select_sql, $join_sql, $has_multiple_values) = self::_appendSelectJoinSqlForCustomFieldTables(
//			$tables,
//			$params,
//			'cwf.id',
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
			//($has_multiple_values ? 'GROUP BY cwf.id ' : '').
			$sort_sql;

		$rs = $db->SelectLimit($sql,$limit,$start) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
		
		$results = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			foreach($rs->fields as $f => $v) {
				$result[$f] = $v;
			}
			$id = intval($rs->fields[SearchFields_Watcher2MailFilter::ID]);
			$results[$id] = $result;
			$rs->MoveNext();
		}

		// [JAS]: Count all
		$total = -1;
		if($withCounts) {
			$count_sql = 
				($has_multiple_values ? "SELECT COUNT(DISTINCT cwf.id) " : "SELECT COUNT(cwf.id) ").
				$join_sql.
				$where_sql;
			$total = $db->GetOne($count_sql);
		}
		
		return array($results,$total);
    }	
	
};

class SearchFields_Watcher2MailFilter {
	// Watcher2_Mail_Filter
	const ID = 'cwf_id';
	const POS = 'cwf_pos';
	const NAME = 'cwf_name';
	const CREATED = 'cwf_created';
	const WORKER_ID = 'cwf_worker_id';
	const IS_DISABLED = 'cwf_is_disabled';
	
	/**
	 * @return DevblocksSearchField[]
	 */
	static function getFields() {
		$translate = DevblocksPlatform::getTranslationService();
		
		$columns = array(
			self::ID => new DevblocksSearchField(self::ID, 'cwf', 'id', null, ucwords($translate->_('common.id'))),
			self::POS => new DevblocksSearchField(self::POS, 'cwf', 'pos', null, ucwords($translate->_('watcher2.filter.model.hits'))),
			self::NAME => new DevblocksSearchField(self::NAME, 'cwf', 'name', null, ucwords($translate->_('common.name'))),
			self::CREATED => new DevblocksSearchField(self::CREATED, 'cwf', 'created', null, ucwords($translate->_('common.created'))),
			self::WORKER_ID => new DevblocksSearchField(self::WORKER_ID, 'cwf', 'worker_id', null, ucwords($translate->_('common.worker'))),
			self::IS_DISABLED => new DevblocksSearchField(self::IS_DISABLED, 'cwf', 'is_disabled', null, ucwords($translate->_('common.disabled'))),
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

class Model_Watcher2MailFilter {
	public $id;
	public $pos;
	public $name;
	public $created;
	public $is_disabled=0;
	public $worker_id;
	public $criteria;
	public $actions;
	
	/**
	 * @return Model_Watcher2MailFilter[]|false
	 */
	static function getMatches(CerberusTicket $ticket, $event, $only_worker_id=null) {
		$matches = array();
		
		if(!empty($only_worker_id)) {
			$filters = DAO_Watcher2MailFilter::getWhere(sprintf("%s = %d AND %s = %d",
				DAO_Watcher2MailFilter::WORKER_ID,
				$only_worker_id,
				DAO_Watcher2MailFilter::IS_DISABLED,
				0
			));
		} else {
			$filters = DAO_Watcher2MailFilter::getWhere(sprintf("%s = %d",
				DAO_Watcher2MailFilter::IS_DISABLED,
				0
			));
		}

		// [JAS]: Don't send obvious spam to watchers2.
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
		foreach($filters as $filter) { /* @var $filter Model_Watcher2MailFilter */
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

						// Forced to English abbrevs as indexes Full names are translated well being displayed
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
				DAO_Watcher2MailFilter::increment($filter->id); // ++ the times we've matched
				$matches[$filter->id] = $filter;
			}
		}
		
		if(!empty($matches))
			return $matches;
		
		// No matches
		return false;
	}
};
class DAO_Watcher2Template extends DevblocksORMHelper {
	const _TABLE = 'cerb5blog_watchers2_template';
	
	const ID = 'id';
	const TITLE = 'title';
	const DESCRIPTION = 'description';
	const TEMPLATE_TYPE = 'template_type';
	const TEMPLATE_SUBJECT = 'template_subject';
	const TEMPLATE_CONTENT = 'template_content';
	const OWNER_ID = 'owner_id';
	
	public static function create($fields) {
		$db = DevblocksPlatform::getDatabaseService();
		$id = $db->GenID('generic_seq');
		
		$sql = sprintf("INSERT INTO %s (id,title,description,template_type,template_subject,template_content,owner_id) ".
			"VALUES (%d,'','',0,'','',0)",
			self::_TABLE,
			$id
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		self::update($id, $fields);
		
		return $id;
	}
	
	public static function update($ids, $fields) {
		// [TODO] Overload CONTENT as BlobUpdate
		parent::_update($ids, self::_TABLE, $fields);
	}
	
	public static function delete($ids) {
		if(!is_array($ids)) $ids = array($ids);
		$db = DevblocksPlatform::getDatabaseService();
		
		if(empty($ids))
			return;
		
		$sql = sprintf("DELETE QUICK FROM %s WHERE id IN (%s)",
			self::_TABLE,
			implode(',', $ids)
		);
		$db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */
	}
	
	public function getByType($type) {
		return self::getWhere(sprintf("%s = %d",
			self::TEMPLATE_TYPE,
			$type
		));
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $where
	 * @return Model_MailTemplate[]
	 */
	public function getWhere($where=null) {
		$db = DevblocksPlatform::getDatabaseService();
		
		$sql = sprintf("SELECT id,title,description,template_type,template_subject,template_content,owner_id ".
			"FROM %s ".
			(!empty($where) ? ("WHERE $where ") : " ").
			" ORDER BY title ",
			self::_TABLE
		);
		$rs = $db->Execute($sql) or die(__CLASS__ . '('.__LINE__.')'. ':' . $db->ErrorMsg()); /* @var $rs ADORecordSet */

		return self::_createObjectsFromResultSet($rs);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return Model_MailTemplate
	 */
	public static function get($id) {
		$objects = self::getWhere(sprintf("id = %d", $id));
		
		if(isset($objects[$id]))
			return $objects[$id];
			
		return null;
	}
	
	public static function _createObjectsFromResultSet(ADORecordSet $rs) {
		$objects = array();
		
		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$object = new Model_MailTemplate();
			$object->id = intval($rs->fields['id']);
			$object->title = $rs->fields['title'];
			$object->description = $rs->fields['description'];
			$object->template_type = intval($rs->fields['template_type']);
			$object->template_subject = $rs->fields['template_subject'];
			$object->template_content = $rs->fields['template_content'];
			$object->owner_id = intval($rs->fields['owner_id']);
			$objects[$object->id] = $object;
			$rs->MoveNext();
		}
		
		return $objects;
	}
};

