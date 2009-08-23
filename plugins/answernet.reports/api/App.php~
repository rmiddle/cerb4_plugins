<?php

class AnswernetReportsPlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

if (class_exists('DevblocksTranslationsExtension',true)):
	class AnswernetTranslations extends DevblocksTranslationsExtension {
		function __construct($manifest) {
			parent::__construct($manifest);
		}

		function getTmxFile() {
			return dirname(dirname(__FILE__)) . '/strings.xml';
		}
	};
endif;

class AnswernetReportGroups extends Extension_ReportGroup {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
};

class AnswernetReportGroupsTime extends Extension_ReportGroup {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
};

class AnswernetReportWorkers extends Extension_Report {
	private $tpl_path = null;

	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}

	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		$tpl->assign('start', '-5 year');
		$tpl->assign('end', 'now');

		$db = DevblocksPlatform::getDatabaseService();

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		// Teams
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);

		// Categories
		$team_categories = DAO_Bucket::getTeams(); // [TODO] Cache these
		$tpl->assign('team_categories', $team_categories);

		$tpl->display('file:' . $this->tpl_path . '/report_stats.tpl');
	}

	function getTicketAssignmentReportAction() {
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		@$countonly = DevblocksPlatform::importGPC($_REQUEST['countonly'],'integer',0);
		@$sel_worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$sel_group_id = DevblocksPlatform::importGPC($_REQUEST['group_select'],'string','');

		list($g_id, $b_id) = CerberusApplication::translateTeamCategoryCode($sel_group_id);

		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		if (empty($start) && empty($end)) {
			$start = "-5 year";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}

		$db = DevblocksPlatform::getDatabaseService();

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		$workers = DAO_Worker::getAll();
		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();
		$tpl->assign('workers', $workers);

		$sql = "SELECT w.id worker_id, t.id ticket_id, t.mask, t.subject, t.created_date, ";
		$sql .= "t.updated_date, t.is_waiting, t.team_id, t.category_id ";
		$sql .= "FROM ticket t inner join worker w on t.next_worker_id = w.id ";
		$sql .= sprintf("WHERE updated_date > %d AND updated_date <= %d ", $start_time, $end_time);
		$sql .= "AND t.is_deleted = 0 ";
		$sql .= "AND t.is_closed = 0 ";
		$sql .= "AND t.spam_score < 0.9000 ";
		$sql .= "AND t.spam_training != 'S' ";
		if ($sel_worker_id) {
        $sql .= sprintf("AND w.id = %d ", $sel_worker_id);
      }
		if ($g_id > 0) {
			$sql .= sprintf("AND t.team_id = %d ", $g_id);
			if ($b_id > -1) {
				$sql .= sprintf("AND t.category_id = %d ", $b_id);
			}
		}
		$sql .= "ORDER by w.last_name";
		$rs_buckets = $db->Execute($sql);

		$ticket_assignments = array();
		while(!$rs_buckets->EOF) {
			$worker_id = intval($rs_buckets->fields['worker_id']);
			$mask = $rs_buckets->fields['mask'];
			$subject = $rs_buckets->fields['subject'];
			$created_date = intval($rs_buckets->fields['created_date']);
			$updated_date = intval($rs_buckets->fields['updated_date']);
      If ($rs_buckets->fields['is_waiting']) {
				$status = "Waiting for Reply";
			}	else {
				$status = "Open";
			}
			$team_id = intval($rs_buckets->fields['team_id']);
			$category_id = intval($rs_buckets->fields['category_id']);

			if(!isset($ticket_assignments[$worker_id]))
				$ticket_assignments[$worker_id] = array();

			unset($assignment);
			$assignment->mask = $mask;
			$assignment->subject = $subject;
			$assignment->created_date = $created_date;
			$assignment->updated_date = $updated_date;
			$assignment->status = $status;
			$assignment->team_id = $groups[$team_id]->name;
      if ( $category_id ) {
			  $assignment->category_id = $buckets[$category_id]->name;
			} else {
			  $assignment->category_id = 'Inbox';
			}
			$ticket_assignments[$worker_id][] = $assignment;

			$rs_buckets->MoveNext();
		}

		$tpl->assign('ticket_assignments', $ticket_assignments);
		//print_r($ticket_assignments);exit;
		$tpl->display('file:' . $this->tpl_path . '/report_stats_html.tpl');
	}

	function getTicketAssignmentChartAction() {
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		@$countonly = DevblocksPlatform::importGPC($_REQUEST['countonly'],'integer',0);
		@$sel_worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$sel_group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'string','');

		list($g_id, $b_id) = CerberusApplication::translateTeamCategoryCode($sel_group_id);

		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		if (empty($start) && empty($end)) {
			$start = "-5 year";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}

		$db = DevblocksPlatform::getDatabaseService();

		$workers = DAO_Worker::getAll();

		$sql = "SELECT w.id worker_id ,count(*) as hits ";
		$sql .= "FROM ticket t inner join worker w on t.next_worker_id = w.id ";
		$sql .= sprintf("WHERE updated_date > %d AND updated_date <= %d ", $start_time, $end_time);
		$sql .= "AND t.is_deleted = 0 ";
		$sql .= "AND t.is_closed = 0 ";
		$sql .= "AND t.spam_score < 0.9000 ";
		$sql .= "AND t.spam_training != 'S' ";
		if ($sel_worker_id) {
        $sql .= sprintf("AND w.id = %d ", $sel_worker_id);
      }
		if ($g_id > 0) {
			$sql .= sprintf("AND t.team_id = %d ", $g_id);
			if ($b_id > -1) {
				$sql .= sprintf("AND t.category_id = %d ", $b_id);
			}
		}
		$sql .= "GROUP by w.id ";
		$sql .= "ORDER by hits ";
		$rs = $db->Execute($sql);

		if($countonly) {
			echo intval($rs->RecordCount());
			return;
		}

		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
	    	$hits = intval($rs->fields['hits']);
			$worker_id = $rs->fields['worker_id'];

			echo $workers[$worker_id]->getName(true), "\t", $hits . "\n";

		    $rs->MoveNext();
	    }
	}
};

if (class_exists('Extension_Report',true)):
class AnswernetReportAssetTime extends Extension_Report {
	private $tpl_path = null;

	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}

	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		$tpl->assign('start', '-30 days');
		$tpl->assign('end', 'now');

		$db = DevblocksPlatform::getDatabaseService();

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		// Teams
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);

		// Categories
		$team_categories = DAO_Bucket::getTeams(); // [TODO] Cache these
		$tpl->assign('team_categories', $team_categories);

		$tpl->display('file:' . $this->tpl_path . '/report_asset_time.tpl');
	}
/*
	function getTimeSpentTicketReportAction() {
		$db = DevblocksPlatform::getDatabaseService();

		@$sel_worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');

		// use date rang@$sel_worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);e if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;

		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}

		if($start_time === false || $end_time === false) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);

			$tpl->assign('invalidDate', true);
		}

		// reload variables in template
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$sources = DAO_TimeTrackingEntry::getSources();
		$tpl->assign('sources', $sources);

		$sql = sprintf("SELECT tte.log_date, tte.time_actual_mins, tte.worker_id, tte.notes, ".
				"tte.source_extension_id, tte.source_id, ".
				"tta.name activity_name, o.name org_name, o.id org_id ".
				"FROM timetracking_entry tte ".
				"INNER JOIN timetracking_activity tta ON tte.activity_id = tta.id ".
				"LEFT JOIN contact_org o ON o.id = tte.debit_org_id ".
				"INNER JOIN worker w ON tte.worker_id = w.id ".
				"WHERE log_date > %d AND log_date <= %d ".
				(($sel_worker_id!=0) ? "AND tte.worker_id = ". $sel_worker_id. ' ' : '') .
				"ORDER BY w.first_name, w.last_name, w.id, tte.log_date ",
			$start_time,
			$end_time
		);
		//echo $sql;
		$rs = $db->Execute($sql);

		$time_entries = array();

		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$mins = intval($rs->fields['time_actual_mins']);
			$worker_id = intval($rs->fields['worker_id']);
			$org_id = intval($rs->fields['org_id']);
			$activity = $rs->fields['activity_name'];
			$org_name = $rs->fields['org_name'];
			$log_date = intval($rs->fields['log_date']);
			$notes = $rs->fields['notes'];


			if(!isset($time_entries[$worker_id]))
				$time_entries[$worker_id] = array();

			unset($time_entry);
			$time_entry['activity_name'] = $activity;
			$time_entry['org_name'] = $org_name;
			$time_entry['mins'] = $mins;
			$time_entry['log_date'] = $log_date;
			$time_entry['notes'] = $notes;
			$time_entry['source_extension_id'] = $rs->fields['source_extension_id'];
			$time_entry['source_id'] = intval($rs->fields['source_id']);

			$time_entries[$worker_id]['entries'][] = $time_entry;
			@$time_entries[$worker_id]['total_mins'] = intval($time_entries[$worker_id]['total_mins']) + $mins;

			$rs->MoveNext();
		}
		//print_r($time_entries);
		$tpl->assign('time_entries', $time_entries);

		$tpl->display('file:' . $this->tpl_path . '/report_ticket_time_html.tpl');
	}
*/

	function getTimeSpentAssetChartAction() {
		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		@$countonly = DevblocksPlatform::importGPC($_REQUEST['countonly'],'integer',0);
		@$sel_worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$sel_group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'string','');

		list($g_id, $b_id) = CerberusApplication::translateTeamCategoryCode($sel_group_id);

		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}

		$db = DevblocksPlatform::getDatabaseService();

		$groups = DAO_Group::getAll();

		$sql = "SELECT sum(tte.time_actual_mins) mins, cfs.field_value ";
		$sql .= "FROM timetracking_entry tte ";
		$sql .= "INNER JOIN ticket t ON tte.source_id = t.id ";
		$sql .= "INNER JOIN custom_field_stringvalue cfs ON t.id = cfs.source_id ";
		$sql .= sprintf("WHERE log_date > %d AND log_date <= %d ", $start_time, $end_time);
		$sql .= "AND tte.source_extension_id = 'timetracking.source.ticket' ";
		$sql .= "AND cfs.field_id = 11 ";
		if ($sel_worker_id) {
        $sql .= sprintf("AND tte.worker_id = %d ", $sel_worker_id);
      }
		if ($g_id > 0) {
			$sql .= sprintf("AND t.team_id = %d ", $g_id);
			if ($b_id > -1) {
				$sql .= sprintf("AND t.category_id = %d ", $b_id);
			}
		}
		$sql .= "GROUP BY cfs.field_value ";
		$sql .= "ORDER BY cfs.field_value ";
		$rs = $db->Execute($sql);

		if($countonly) {
			echo intval($rs->RecordCount());
			return;
		}

	    if(is_a($rs,'ADORecordSet'))
	    while(!$rs->EOF) {
	    	$mins = intval($rs->fields['mins']);
			$field_value = $rs->fields['field_value'];
			echo $field_value, "\t", $mins . "\n";
			$rs->MoveNext();
	    }
	}
};
endif;

if (class_exists('Extension_Report',true)):
class AnswernetReportClientTime extends Extension_Report {
	private $tpl_path = null;

	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}

	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		$tpl->assign('start', '-30 days');
		$tpl->assign('end', 'now');

		$db = DevblocksPlatform::getDatabaseService();

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		// Teams
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);

		// Categories
		$team_categories = DAO_Bucket::getTeams(); // [TODO] Cache these
		$tpl->assign('team_categories', $team_categories);

		$tpl->display('file:' . $this->tpl_path . '/report_client_time.tpl');
	}

	function getTimeSpentClientChartAction() {
		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		@$countonly = DevblocksPlatform::importGPC($_REQUEST['countonly'],'integer',0);
		@$sel_worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$sel_group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'string','');

		list($g_id, $b_id) = CerberusApplication::translateTeamCategoryCode($sel_group_id);

		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}

		$db = DevblocksPlatform::getDatabaseService();

		$groups = DAO_Group::getAll();

		$sql = "SELECT sum(tte.time_actual_mins) mins, cfs.field_value ";
		$sql .= "FROM timetracking_entry tte ";
		$sql .= "INNER JOIN ticket t ON tte.source_id = t.id ";
		$sql .= "INNER JOIN custom_field_stringvalue cfs ON t.id = cfs.source_id ";
		$sql .= sprintf("WHERE log_date > %d AND log_date <= %d ", $start_time, $end_time);
		$sql .= "AND tte.source_extension_id = 'timetracking.source.ticket' ";
		$sql .= "AND cfs.field_id = 10 ";
		if ($sel_worker_id) {
        $sql .= sprintf("AND tte.worker_id = %d ", $sel_worker_id);
      }
		if ($g_id > 0) {
			$sql .= sprintf("AND t.team_id = %d ", $g_id);
			if ($b_id > -1) {
				$sql .= sprintf("AND t.category_id = %d ", $b_id);
			}
		}
		$sql .= "GROUP BY cfs.field_value ";
		$sql .= "ORDER BY cfs.field_value ";
		$rs = $db->Execute($sql);

		if($countonly) {
			echo intval($rs->RecordCount());
			return;
		}

	    if(is_a($rs,'ADORecordSet'))
	    while(!$rs->EOF) {
	    	$mins = intval($rs->fields['mins']);
			$field_value = $rs->fields['field_value'];
			echo $field_value, "\t", $mins . "\n";
			$rs->MoveNext();
	    }
	}
};
endif;

if (class_exists('Extension_Report',true)):
class AnswernetReportSiteNameTime extends Extension_Report {
	private $tpl_path = null;

	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}

	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		$tpl->assign('start', '-30 days');
		$tpl->assign('end', 'now');

		$db = DevblocksPlatform::getDatabaseService();

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		// Teams
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);

		// Categories
		$team_categories = DAO_Bucket::getTeams(); // [TODO] Cache these
		$tpl->assign('team_categories', $team_categories);

		$tpl->display('file:' . $this->tpl_path . '/report_sitename_time.tpl');
	}

	function getTimeSpentSiteNameChartAction() {
		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		@$countonly = DevblocksPlatform::importGPC($_REQUEST['countonly'],'integer',0);
		@$sel_worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$sel_group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'string','');

		list($g_id, $b_id) = CerberusApplication::translateTeamCategoryCode($sel_group_id);

		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}

		$db = DevblocksPlatform::getDatabaseService();

		$groups = DAO_Group::getAll();

		$sql = "SELECT sum(tte.time_actual_mins) mins, cfs.field_value ";
		$sql .= "FROM timetracking_entry tte ";
		$sql .= "INNER JOIN ticket t ON tte.source_id = t.id ";
		$sql .= "INNER JOIN custom_field_stringvalue cfs ON t.id = cfs.source_id ";
		$sql .= sprintf("WHERE log_date > %d AND log_date <= %d ", $start_time, $end_time);
		$sql .= "AND tte.source_extension_id = 'timetracking.source.ticket' ";
		$sql .= "AND cfs.field_id = 1 ";
		if ($sel_worker_id) {
			$sql .= sprintf("AND tte.worker_id = %d ", $sel_worker_id);
		}
		if ($g_id > 0) {
			$sql .= sprintf("AND t.team_id = %d ", $g_id);
			if ($b_id > -1) {
				$sql .= sprintf("AND t.category_id = %d ", $b_id);
			}
		}
		$sql .= "GROUP BY cfs.field_value ";
		$sql .= "ORDER BY cfs.field_value ";
		$rs = $db->Execute($sql);

		if($countonly) {
			echo intval($rs->RecordCount());
			return;
		}

	    if(is_a($rs,'ADORecordSet'))
	    while(!$rs->EOF) {
	    	$mins = intval($rs->fields['mins']);
			$field_value = $rs->fields['field_value'];
			echo $field_value, "\t", $mins . "\n";
			$rs->MoveNext();
	    }
	}
};
endif;

if (class_exists('Extension_Report',true)):
class AnswernetReportTicketTime extends Extension_Report {
	private $tpl_path = null;

	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}

	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		$tpl->assign('start', '-30 days');
		$tpl->assign('end', 'now');

		$db = DevblocksPlatform::getDatabaseService();

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		// Teams
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);

		// Categories
		$team_categories = DAO_Bucket::getTeams(); // [TODO] Cache these
		$tpl->assign('team_categories', $team_categories);

		$tpl->display('file:' . $this->tpl_path . '/report_ticket_time.tpl');
	}

	function getTimeSpentTicketChartAction() {
		$sql_array = array();

		// import form data
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');
		@$countonly = DevblocksPlatform::importGPC($_REQUEST['countonly'],'integer',0);
		@$sel_worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$sel_group_id = DevblocksPlatform::importGPC($_REQUEST['group_id'],'string','');

		list($g_id, $b_id) = CerberusApplication::translateTeamCategoryCode($sel_group_id);

		// use date range if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;
		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}

		$db = DevblocksPlatform::getDatabaseService();

//		$groups = DAO_Group::getAll();
//		$buckets = DAO_Bucket::getAll();

		$sql = "SELECT sum(tte.time_actual_mins) mins, t.mask ";
		$sql .= "FROM timetracking_entry tte ";
		$sql .= "INNER JOIN ticket t ON tte.source_id = t.id ";
		$sql .= sprintf("WHERE log_date > %d AND log_date <= %d ", $start_time, $end_time);
		$sql .= "AND tte.source_extension_id = 'timetracking.source.ticket' ";
		if ($sel_worker_id) {
        $sql .= sprintf("AND tte.worker_id = %d ", $sel_worker_id);
      }
		if ($g_id > 0) {
			$sql .= sprintf("AND t.team_id = %d ", $g_id);
			if ($b_id > -1) {
				$sql .= sprintf("AND t.category_id = %d ", $b_id);
			}
		}
		$sql .= "GROUP BY t.mask ";
		$sql .= "ORDER BY mins ";

		$rs = $db->Execute($sql);

		if($countonly) {
			echo intval($rs->RecordCount());
			return;
		}

	    if(is_a($rs,'ADORecordSet'))
	    while(!$rs->EOF) {
	    	$mins = intval($rs->fields['mins']);
			$ticket_mask = $rs->fields['mask'];

			echo $ticket_mask, "\t", $mins . "\n";

		    $rs->MoveNext();
	    }
	}
};
endif;

if (class_exists('Extension_Report',true)):
class AnswernetReportWorkerTime extends Extension_Report {
	private $tpl_path = null;

	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}

	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		$tpl->assign('start', 'Last Monday');
		$tpl->assign('end', 'now');

		$db = DevblocksPlatform::getDatabaseService();

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		// Teams
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);

		// Categories
		$team_categories = DAO_Bucket::getTeams(); // [TODO] Cache these
		$tpl->assign('team_categories', $team_categories);

		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			die($translate->_('common.access_denied'));

		$tpl->assign('active_worker', $active_worker);

		$filename = "worker-".$active_worker->id.".csv";
		$href_filename = 'storage/answernet/'.$filename;
		$tpl->assign('href_filename', $href_filename);

		$tpl->display('file:' . $this->tpl_path . '/report_worker_time.tpl');
	}

	function getTimeSpentWorkerReportAction() {
		$db = DevblocksPlatform::getDatabaseService();
		$subtotal = array();
		$total_cm = array();
    DevblocksPlatform::getExtensions('timetracking.source', true);

		@$sel_worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);
		@$report_type = DevblocksPlatform::importGPC($_REQUEST['report_type'],'integer',0);

		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			die($translate->_('common.access_denied'));

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');

		// use date rang@$sel_worker_id = DevblocksPlatform::importGPC($_REQUEST['worker_id'],'integer',0);e if specified, else use duration prior to now
		$start_time = 0;
		$end_time = 0;

		if (empty($start) && empty($end)) {
			$start = "Last Monday";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}

		if($start_time === false || $end_time === false) {
			$start = "Last Monday";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);

			$tpl->assign('invalidDate', true);
		}

		// reload variables in template
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		$sources = DAO_TimeTrackingEntry::getSources();
		$tpl->assign('sources', $sources);

		$sql = "SELECT tte.log_date, tte.time_actual_mins, tte.worker_id, tte.notes, ";
		$sql .= "tte.source_extension_id, tte.source_id, ";
		$sql .= "tta.name activity_name ";
		$sql .= "FROM timetracking_entry tte ";
		$sql .= "INNER JOIN timetracking_activity tta ON tte.activity_id = tta.id ";
		$sql .= "INNER JOIN worker w ON tte.worker_id = w.id ";
		$sql .= sprintf("WHERE log_date > %d AND log_date <= %d ", $start_time, $end_time);
		if ($sel_worker_id) {
        $sql .= sprintf("AND tte.worker_id = %d ", $sel_worker_id);
      }
//		Do Not use Group By it breaks things.
//		$sql .= "GROUP BY activity_name ";
		$sql .= "ORDER BY w.last_name, w.first_name, activity_name, w.id, tte.log_date ";

		// echo $sql;
		$rs = $db->Execute($sql);

		$time_entries = array();

		$filename = "worker-".$active_worker->id.".csv";
		$full_filename = getcwd().'/storage/answernet/'.$filename;
		if (file_exists($full_filename)):
			if(!is_writable($full_filename)):
				die ("The file: $full_filename is not writable");
			endif;
		elseif( !is_writable( getcwd().'/storage/answernet/' ) ):
			die("you cannot create files in this directory.  Check the permissions");
		endif;
		//open the file for Writing
		$fh = fopen($full_filename, "w");
		//Lock the file for the write operation
		flock($fh, LOCK_EX);
		$label = array( "Worker Name", "Ticket No", "Client", "Asset", "Site Name", "Billing Group", "Billing Min", "Sub-Total", "Total", "Date Recorded", "Notes");
		fputcsv($fh, $label, ",", "\"");

		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$csv = array();
			$custom_fields = array();
			$mins = intval($rs->fields['time_actual_mins']);
			$worker_id = intval($rs->fields['worker_id']);
			$org_id = intval($rs->fields['org_id']);
			$activity = $rs->fields['activity_name'];
			$log_date = intval($rs->fields['log_date']);
			$notes = $rs->fields['notes'];

			if(!isset($time_entries[$worker_id])) {
				$time_entries[$worker_id] = array();
				$time_entries[$worker_id]['mins'] = array();
			}

			if(!isset($subtotal)) {
				$subtotal = array();
				$subtotal_activity = $activity;
				$subtotal['name'] = $workers[$worker_id]->getName(false);
				$subtotal['source_id'] = "";
				$subtotal['client'] = "";
				$subtotal['asset'] = "";
				$subtotal['sitename'] = "";
				$subtotal['activity_name'] = $activity;
				$subtotal['mins'] = "";
			} else if ($subtotal_activity != $activity) {
				//Dump Sub Total
				fputcsv($fh, $subtotal, ",", "\"");
				$subtotal = array();
				$subtotal_activity = $activity;
				$subtotal['name'] = $workers[$worker_id]->getName(false);
				$subtotal['source_id'] = "";
				$subtotal['client'] = "";
				$subtotal['asset'] = "";
				$subtotal['sitename'] = "";
				$subtotal['activity_name'] = $activity;
				$subtotal['mins'] = "";
			}

			if(!isset($total_cm)) {
				$total_cm = array();
				$total_worker_id = $worker_id;
				$total_cm['name'] = $workers[$worker_id]->getName(false);
				$total_cm['source_id'] = "";
				$total_cm['client'] = "";
				$total_cm['asset'] = "";
				$total_cm['sitename'] = "";
				$total_cm['activity_name'] = "";
				$total_cm['mins'] = "";
			} else if ($total_worker_id != $worker_id) {
				//Dump Total
				fputcsv($fh, $total_cm, ",", "\"");
				$total_cm = array();
				$total_worker_id = $worker_id;
				$total_cm['name'] = $workers[$worker_id]->getName(false);
				$total_cm['source_id'] = "";
				$total_cm['client'] = "";
				$total_cm['asset'] = "";
				$total_cm['sitename'] = "";
				$total_cm['activity_name'] = "";
				$total_cm['mins'] = "";
			}

			$csv['name'] = $workers[$worker_id]->getName(false);

			unset($time_entry);
			$time_entry['activity_name'] = $activity;
			$time_entry['mins'] = $mins;
			$time_entry['log_date'] = $log_date;
			$time_entry['notes'] = $notes;
			$time_entry['source_extension_id'] = $rs->fields['source_extension_id'];
			$time_entry['source_id'] = intval($rs->fields['source_id']);

			$csv['source_id'] = intval($rs->fields['source_id']);
			$custom_fields = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $csv['source_id']);
			if(isset($custom_fields[$csv['source_id']][10])) {
				$csv['client'] = $custom_fields[$csv['source_id']][10];
			} else {
				$csv['client'] = "";
			}
			if(isset($custom_fields[$csv['source_id']][11])) {
				$csv['asset'] = $custom_fields[$csv['source_id']][11];
			} else {
				$csv['asset'] = "";
			}
			if(isset($custom_fields[$csv['source_id']][1])) {
				$csv['sitename'] = $custom_fields[$csv['source_id']][1];
			} else {
				$csv['sitename'] = "";
			}
			$csv['activity_name'] = $activity;
			$csv['mins'] = $mins;
			$csv['subtotal'] = "";
			$csv['total'] = "";
			$csv['log_date'] = date("Y-m-d h:i A", $log_date);
			$csv['notes'] = $notes;
			$time_entries[$worker_id]['entries'][] = $time_entry;
			@$time_entries[$worker_id]['mins'][$activity] = intval($time_entries[$worker_id]['mins'][$activity]) + $mins;
			@$time_entries[$worker_id]['mins']['total'] = intval($time_entries[$worker_id]['mins']['total']) + $mins;

			$subtotal['subtotal'] = $time_entries[$worker_id]['mins'][$activity];
			$total_cm['subtotal'] = "";

			$subtotal['total'] = "";
			$total_cm['total'] = $time_entries[$worker_id]['mins']['total'];

			fputcsv($fh, $csv, ",", "\"");
			$rs->MoveNext();
		}
		// print_r($time_entries);
		$tpl->assign('time_entries', $time_entries);
		$tpl->assign('report_type', $report_type);
		$tpl->assign('href_filename', $href_filename);

		fputcsv($fh, $subtotal, ",", "\"");
		fputcsv($fh, $total_cm, ",", "\"");
		fclose($fh);

		$tpl->display('file:' . $this->tpl_path . '/report_worker_time_html.tpl');
	}

};
endif;
if (class_exists('Extension_Report',true)):
class AnswernetReportPlus1Time extends Extension_Report {
	private $tpl_path = null;

	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}

	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		$tpl->assign('start', '-30 days');
		$tpl->assign('end', 'now');

		$db = DevblocksPlatform::getDatabaseService();

		$workers = DAO_Worker::getAll();
		$tpl->assign('workers', $workers);

		// Teams
		$teams = DAO_Group::getAll();
		$tpl->assign('teams', $teams);

		// Categories
		$team_categories = DAO_Bucket::getTeams(); // [TODO] Cache these
		$tpl->assign('team_categories', $team_categories);

		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			die($translate->_('common.access_denied'));

		$tpl->assign('active_worker', $active_worker);

		$filename = "report-plus1-".$active_worker->id.".csv";
		$href_filename = 'storage/answernet/'.$filename;
		$tpl->assign('href_filename', $href_filename);

		$tpl->display('file:' . $this->tpl_path . '/report_plus1_time.tpl');
	}

	function getTimeSpentPlus1ReportAction() {
		$db = DevblocksPlatform::getDatabaseService();
    DevblocksPlatform::getExtensions('timetracking.source', true);

		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			die($translate->_('common.access_denied'));

		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		// import dates from form
		@$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
		@$end = DevblocksPlatform::importGPC($_REQUEST['end'],'string','');

		$start_time = 0;
		$end_time = 0;

		if (empty($start) && empty($end)) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		} else {
			$start_time = strtotime($start);
			$end_time = strtotime($end);
		}

		if($start_time === false || $end_time === false) {
			$start = "-30 days";
			$end = "now";
			$start_time = strtotime($start);
			$end_time = strtotime($end);

			$tpl->assign('invalidDate', true);
		}

		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();

		// reload variables in template
		$tpl->assign('start', $start);
		$tpl->assign('end', $end);

    $sources = DAO_TimeTrackingEntry::getSources();
		$tpl->assign('sources', $sources);

		$sql = "SELECT t.mask, t.id, sum(tte.time_actual_mins) mins, a.email, ";
		$sql .= "t.subject, t.created_date, t.updated_date, t.is_closed, ";
		$sql .= "t.is_waiting, t.team_id, t.category_id ";
		$sql .= "FROM timetracking_entry tte ";
		$sql .= "INNER JOIN ticket t ON  tte.source_id = t.id  ";
		$sql .= "INNER JOIN address a ON t.first_wrote_address_id = a.id ";
		$sql .= sprintf("WHERE log_date > %d AND log_date <= %d ", $start_time, $end_time);
		$sql .= "GROUP BY t.id ";
		$sql .= "ORDER BY t.id, tte.log_date ";

		// echo $sql;
		$rs = $db->Execute($sql);

		$time_entries = array();

		$filename = "report-plus1-".$active_worker->id.".csv";
		$full_filename = getcwd().'/storage/answernet/'.$filename;
		if (file_exists($full_filename)):
			if(!is_writable($full_filename)):
				die ("The file: $full_filename is not writable");
			endif;
		elseif( !is_writable( getcwd().'/storage/answernet/' ) ):
			die("you cannot create files in this directory.  Check the permissions");
		endif;
		//open the file for Writing
		$fh = fopen($full_filename, "w");
		//Lock the file for the write operation
		flock($fh, LOCK_EX);

		$label = array( "Ticket Mask", "Ticket Number", "Client Name", "Asset Name", "Site Name", "Requestor", "Subject", "Created Date", "Last Updated", "Group", "Bucket", "Status", "Total Min");
		fputcsv($fh, $label, ",", "\"");

		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
			$csv = array();
			$custom_fields = array();

			$mask = $rs->fields['mask'];
			$id = intval($rs->fields['id']);
			$email = $rs->fields['email'];
			$subject = $rs->fields['subject'];

			$team_id = intval($rs->fields['team_id']);
			$category_id = intval($rs->fields['category_id']);

			$created_date = intval($rs->fields['created_date']);
			$updated_date = intval($rs->fields['updated_date']);
			$status = "Open";
			if (intval($rs->fields['is_waiting'])) {
				$status = "Waiting for Reply";
			}
			if (intval($rs->fields['is_closed'])) {
				$status = "Completed";
			}
			$mins = intval($rs->fields['mins']);

			if(!isset($time_entries[$id])) {
				$time_entries[$id] = array();
			}

			$csv['mask'] = $mask;
			$csv['id'] = $id;

			unset($time_entry);
			$time_entry['mask'] = $mask;

			$custom_fields = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $id);
			if(isset($custom_fields[$id][10])) {
				$csv['client'] = $custom_fields[$id][10];
				$time_entry['client'] = $custom_fields[$id][10];
			} else {
				$csv['client'] = "";
				$time_entry['client'] = "";
			}
			if(isset($custom_fields[$id][11])) {
				$csv['asset'] = $custom_fields[$id][11];
				$time_entry['asset'] = $custom_fields[$id][11];
			} else {
				$csv['asset'] = "";
				$time_entry['asset'] = "";
			}
			if(isset($custom_fields[$id][1])) {
				$csv['sitename'] = $custom_fields[$id][1];
				$time_entry['sitename'] = $custom_fields[$id][1];
			} else {
				$csv['sitename'] = "";
				$time_entry['sitename'] = "";
			}
			$csv['email'] = $email;
			$time_entry['email'] = $email;
			$csv['subject'] = $subject;
			$time_entry['subject'] = $subject;
			$csv['created_date'] = date("Y-m-d h:i A", $created_date);
			$time_entry['created_date'] = $created_date;
			$csv['updated_date'] = date("Y-m-d h:i A", $updated_date);
			$time_entry['updated_date'] = $updated_date;
			$csv['group'] = $groups[$team_id]->name;
			$time_entry['group'] = $groups[$team_id]->name;
	      if ( $category_id ) {
				$csv['bucket'] = $buckets[$category_id]->name;
				$time_entry['bucket'] = $buckets[$category_id]->name;
			} else {
				$csv['bucket'] = 'Inbox';
				$time_entry['bucket'] = 'Inbox';
			}
			$csv['status'] = $status;
			$time_entry['status'] = $status;

			$csv['mins'] = $mins;
			$time_entry['mins'] = $mins;
			$time_entries[$id] = $time_entry;

			fputcsv($fh, $csv, ",", "\"");
			$rs->MoveNext();
		}
		fclose($fh);
		$tpl->assign('time_entries', $time_entries);
		$tpl->display('file:' . $this->tpl_path . '/report_plus1_time_html.tpl');
	}

};
endif;

?>
