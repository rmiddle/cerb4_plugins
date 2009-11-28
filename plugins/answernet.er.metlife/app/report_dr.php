<?php

require_once 'Spreadsheet/Excel/Writer.php';

if (class_exists('Extension_Report',true)):
class AnswernetMetlifeReportGroupReportDR extends Extension_Report {
	private $tpl_path = null;

	function __construct($manifest) {
		parent::__construct($manifest);
		$this->tpl_path = dirname(dirname(__FILE__)).'/templates';
	}

	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->cache_lifetime = "0";
		$tpl->assign('path', $this->tpl_path);

		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			die($translate->_('common.access_denied'));

    $start = "Yesterday";
    $tpl->assign('start', $start);

		$tpl->display('file:' . $this->tpl_path . '/report_metlife_dr.tpl');
	}

  function AnswernetMetlifeReportDRAction() {
    $translate = DevblocksPlatform::getTranslationService();
    $url = DevblocksPlatform::getUrlService();

    $filename = self::AnswernetMetlifeReportDRReport(0);
    $full_filename = getcwd().'/storage/answernet/'.$filename;
    $href_filename = $url->write('storage/answernet/'.$filename, true);

    print $translate->_('answernet.er.metlife.metlife.done');
    print '<br>';
    print $translate->_('answernet.er.metlife.generating');
    print $translate->_('answernet.er.metlife.metlife.done');
    print '<br><br>';
    print '<b><a href=' . $href_filename . '>' . $translate->_('answernet.er.metlife.download.xls') . '</a></b>';
    print '<br><br>';
  }

	function AnswernetMetlifeReportDRReport($RunFromCron = 0) {
		$db = DevblocksPlatform::getDatabaseService();
		$translate = DevblocksPlatform::getTranslationService();

    if ($RunFromCron) {
      $logger = DevblocksPlatform::getConsoleLog();
    }

    $radius = 12;
    $start_time = 0;
    date_default_timezone_set('Etc/UTC');
		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			die($translate->_('common.access_denied'));

		// import dates from form

    @$start = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
    if (empty($start)) {
      $start = "Yesterday";
    }
    $start_time = strtotime($start);
    if ($start_time === false) {
      $start = "Yesterday";
      $start_time = strtotime($start);
    }
		if (empty($start_time) || !is_numeric($start_time)) {
      return;
    }

    $start_ofday = mktime(0, 0, 0, date("m", $start_time), date("d", $start_time), date("Y", $start_time));
    $end_ofday = $start_ofday + 86400;

    if ($RunFromCron) {
      $logger->info("[Answernet.com] Gerating the Metlife DR Report.");
      $logger->info("[Answernet.com] " .
        $translate->_('answernet.er.metlife.report.dr.date.from.text') .
        date(" Y-m-d H:i:s ", $start_ofday) .
        $translate->_('answernet.er.metlife.report.dr.date.to.text') .
        date(" Y-m-d H:i:s T", $end_ofday)
        );
      $logger->info("[Answernet.com] " . $translate->_('answernet.er.metlife.generate.report'));
    } else {
      print '<br><br><br>';
      print $translate->_('answernet.er.metlife.report.dr.date.from.text');
      print date(" Y-m-d H:i:s ", $start_ofday);    
      print $translate->_('answernet.er.metlife.report.dr.date.to.text');
      print date(" Y-m-d H:i:s T", $end_ofday);    
      print '<br>';
      print $translate->_('answernet.er.metlife.generate.report');
    }

    $filename = "report-metlife-dr-" . date("Ymd", $start_time) . ".xls";
    $full_filename = getcwd().'/storage/answernet/'.$filename;

    if ($RunFromCron) {
      $logger->info("[Answernet.com] " . $translate->_('answernet.er.metlife.generating'));
    } else {
      print '<br>';
      print $translate->_('answernet.er.metlife.generating');
    }

    // Create new Excel Spreadsheet.
    $workbook = new Spreadsheet_Excel_Writer($full_filename);

    // Create Open Status Tab and set Column Width and Row Hight.
    $worksheet_transaction=& $workbook->addWorksheet('Transaction Report');
    $worksheet_transaction->setColumn(0, 0, $radius*0.85);
    $worksheet_transaction->setColumn(1, 1, $radius*0.85);
    $worksheet_transaction->setColumn(2, 2, $radius*0.35);
    $worksheet_transaction->setColumn(3, 3, $radius*1.65);
    $worksheet_transaction->setColumn(4, 4, $radius*1.85);
    $worksheet_transaction->setColumn(5, 5, $radius*1.16);
    $worksheet_transaction->setColumn(6, 6, $radius*2.60);
    $worksheet_transaction->setColumn(7, 8, $radius*0.87);
    $worksheet_transaction->setColumn(9, 9, $radius*3.28);
    $worksheet_transaction->setColumn(10, 10, $radius*1.34);
//    $worksheet_open_status->setRow(0, 28);
//    $worksheet_open_status->setRow(2, 32);

    // Create Open Status Tab and set Column Width and Row Hight.
    $worksheet_open_status =& $workbook->addWorksheet('Open DR Report');
    $worksheet_open_status->setColumn(0, 0, $radius*0.85);
    $worksheet_open_status->setColumn(1, 1, $radius*0.35);
    $worksheet_open_status->setColumn(2, 2, $radius*0.70);
    $worksheet_open_status->setColumn(3, 3, $radius*1.65);
    $worksheet_open_status->setColumn(4, 4, $radius*1.85);
    $worksheet_open_status->setColumn(5, 5, $radius*1.16);
    $worksheet_open_status->setColumn(6, 6, $radius*2.60);
    $worksheet_open_status->setColumn(7, 8, $radius*0.87);
    $worksheet_open_status->setColumn(9, 9, $radius*3.28);
    $worksheet_open_status->setColumn(10, 10, $radius*1.34);
//    $worksheet_open_status->setRow(0, 28);
//    $worksheet_open_status->setRow(2, 32);

    // Create monthly Tab and set Column Width and Row Hight.
    $worksheet_monthly =& $workbook->addWorksheet('Month DR Summary');
    $worksheet_monthly->setColumn(0, 0, $radius*2.46);
    $worksheet_monthly->setColumn(1, 1, $radius*.47);
    $worksheet_monthly->setColumn(2, 2, $radius*.63);
    $worksheet_monthly->setColumn(3, 3, $radius*.69);
    $worksheet_monthly->setColumn(4, 4, $radius*.69);
    $worksheet_monthly->setColumn(5, 5, $radius*.69);
//    $worksheet_monthly->setRow(0, 56);

// Formats used thoughout the workbook.
    $format_general =& $workbook->addFormat();
    $format_general->setBorder(1);
    $format_general->setHAlign('left');
    $format_general->setTextWrap();

    $format_general_title =& $workbook->addFormat();
    $format_general_title->setBorder(1);
    $format_general_title->setColor('black');
    $format_general_title->setFgColor('silver');
    $format_general_title->setHAlign('left');
    $format_general_title->setVAlign('vjustify');
    $format_general_title->setVAlign('top');

    $format_general_nowrap =& $workbook->addFormat();
    $format_general_nowrap->setBorder(1);

    // Setup templating for the formating of certain cells in the Monthly Group.
    $format_monthly_title =& $workbook->addFormat();
    $format_monthly_title->setColor(8);
    $format_monthly_title->setFgColor(35);
    $format_monthly_title->setBorder(1);
    $format_monthly_title->setBold();
    $format_monthly_title->setHAlign('center');
    $format_monthly_title->setVAlign('vjustify');
    $format_monthly_title->setVAlign('top');
    $format_monthly_title->setTextWrap();
    $format_monthly_title->setAlign('merge');

// Added headers since they never change in the transaction Group.
    $worksheet_transaction->write(0, 0, 'Status', $format_general_title);
    $worksheet_transaction->write(0, 1, 'Due Date', $format_general_title);
    $worksheet_transaction->write(0, 2, 'SLA', $format_general_title);
    $worksheet_transaction->write(0, 3, 'Date Received', $format_general_title);
    $worksheet_transaction->write(0, 4, 'RM Name', $format_general_title);
    $worksheet_transaction->write(0, 5, 'RM Employee id', $format_general_title);
    $worksheet_transaction->write(0, 6, 'Request Type', $format_general_title);
    $worksheet_transaction->write(0, 7, 'MetLife Staff', $format_general_title);
    $worksheet_transaction->write(0, 8, 'New Hire', $format_general_title);
    $worksheet_transaction->write(0, 9, 'Nates (email body)', $format_general_title);
    $worksheet_transaction->write(0, 10, 'Ticket Mask', $format_general_title);

// Added headers since they never change in the Open Status Group.
    $worksheet_open_status->write(0, 0, 'Due Date', $format_general_title);
    $worksheet_open_status->write(0, 1, 'SLA', $format_general_title);
    $worksheet_open_status->write(0, 2, 'Overdue', $format_general_title);
    $worksheet_open_status->write(0, 3, 'Date Received', $format_general_title);
    $worksheet_open_status->write(0, 4, 'RM Name', $format_general_title);
    $worksheet_open_status->write(0, 5, 'RM Employee id', $format_general_title);
    $worksheet_open_status->write(0, 6, 'Request Type', $format_general_title);
    $worksheet_open_status->write(0, 7, 'MetLife Staff', $format_general_title);
    $worksheet_open_status->write(0, 8, 'New Hire', $format_general_title);
    $worksheet_open_status->write(0, 9, 'Nates (email body)', $format_general_title);
    $worksheet_open_status->write(0, 10, 'Ticket Mask', $format_general_title);

// Added headers since they never change in the monthly Group.
    $month_text = date("F-y", $start_time);
    $worksheet_monthly->write(0, 0, $month_text, $format_monthly_title);
    $worksheet_monthly->write(0, 1, '', $format_monthly_title);
    $worksheet_monthly->write(0, 2, '', $format_monthly_title);
    $worksheet_monthly->write(0, 3, '', $format_monthly_title);
    $worksheet_monthly->write(0, 4, '', $format_monthly_title);
    $worksheet_monthly->write(0, 5, '', $format_monthly_title);

    $worksheet_monthly->write(1, 0, 'DR Summary', $format_general_title);
    $worksheet_monthly->write(1, 1, '', $format_general_title);

    $worksheet_monthly->write(2, 0, 'All DRs incoming*', $format_general);
    $worksheet_monthly->write(3, 0, 'DRs Sent to MetLife', $format_general);
    $worksheet_monthly->write(4, 0, 'DRs Completed', $format_general);
    $worksheet_monthly->write(5, 0, 'Average time to reply (day)', $format_general);
    $worksheet_monthly->write(6, 0, 'Missed DR SLA', $format_general);
    $worksheet_monthly->write(7, 0, 'DR escalations', $format_general);
    $worksheet_monthly->write(8, 0, 'DR Unique Users', $format_general);
    $worksheet_monthly->write(9, 0, 'New Hires (30 Days)', $format_general);
    $worksheet_monthly->write(10, 0, '', $format_general);
    $worksheet_monthly->write(10, 1, '', $format_general);

    $worksheet_monthly->write(11, 0, 'DR Categories', $format_general_title);
    $worksheet_monthly->write(11, 1, '#s', $format_general_title);
    $worksheet_monthly->write(11, 2, 'Avg SLA', $format_general_title);
    $worksheet_monthly->write(11, 3, 'Linda #s', $format_general_title);
    $worksheet_monthly->write(11, 4, 'Colin #s', $format_general_title);
    $worksheet_monthly->write(11, 5, 'Sarfraz #s', $format_general_title);

    $worksheet_monthly->write(12, 0, 'Import Contacts - New hire', $format_general);
    $worksheet_monthly->write(13, 0, 'Import Contacts', $format_general);
    $worksheet_monthly->write(14, 0, 'Create mailing list from exiting date', $format_general);
    $worksheet_monthly->write(15, 0, 'Update existing contacts - batch', $format_general);
    $worksheet_monthly->write(16, 0, 'Missing or incorrect customer info', $format_general);
    $worksheet_monthly->write(17, 0, 'Fix duplicate contacts', $format_general);
    $worksheet_monthly->write(18, 0, 'Export third party file', $format_general);
    $worksheet_monthly->write(19, 0, 'Other', $format_general);
    $worksheet_monthly->write(20, 0, 'Total', $format_general_title);
    $worksheet_monthly->write(20, 1, '', $format_general_title);
    $worksheet_monthly->write(20, 2, '', $format_general_title);
    $worksheet_monthly->write(20, 3, '', $format_general_title);
    $worksheet_monthly->write(20, 4, '', $format_general_title);
    $worksheet_monthly->write(20, 5, '', $format_general_title);

    $worksheet_monthly->write(22, 0, '* Some DRs will be deemed normal care and should use other reporting codes, remove from DR reporting', $format_general_nowrap);
    $worksheet_monthly->write(23, 0, '** Days should be tracked as business days', $format_general_nowrap);

    $worksheet_monthly->write(25, 0, 'SLA Goals', $format_general_title);
    $worksheet_monthly->write(25, 1, '', $format_general_title);

    $worksheet_monthly->write(26, 0, 'Import Contacts - New hire', $format_general);
    $worksheet_monthly->write(27, 0, 'Import Contacts', $format_general);
    $worksheet_monthly->write(28, 0, 'Create mailing list from exiting date', $format_general);
    $worksheet_monthly->write(29, 0, 'Update existing contacts - batch', $format_general);
    $worksheet_monthly->write(30, 0, 'Missing or incorrect customer info', $format_general);
    $worksheet_monthly->write(31, 0, 'Fix duplicate contacts', $format_general);
    $worksheet_monthly->write(32, 0, 'Export third party file', $format_general);
    $worksheet_monthly->write(33, 0, 'Other', $format_general);
    $worksheet_monthly->write(34, 0, 'Avgerage', $format_general_title);
    $worksheet_monthly->write(34, 1, '', $format_general_title);

    if ($RunFromCron) {
      $logger->info("[Answernet.com] " . $translate->_('answernet.er.metlife.metlife.done'));
      $logger->info("[Answernet.com] " . $translate->_('answernet.er.metlife.generating.dr.daily.report'));
    } else {
      print $translate->_('answernet.er.metlife.metlife.done');
      print '<br>';
      print $translate->_('answernet.er.metlife.generating.dr.daily.report');
    }

//SELECT t.id, t.mask, t.created_date ticket_created_date, mc.content, t.is_closed 
//FROM ticket t 
//INNER JOIN message_content mc on t.first_message_id = mc.message_id 
//INNER JOIN message m on t.first_message_id = m.id 
//and team_id = 1721
//ORDER BY t.id
    $sql = "SELECT t.id, t.mask, ";
    $sql .= "t.created_date ticket_created_date, mc.content ";
    $sql .= "FROM ticket t ";
    $sql .= "INNER JOIN message_content mc on t.first_message_id = mc.message_id ";
    $sql .= "WHERE t.is_closed = 0 ";
    $sql .= "and t.team_id = 1721 ";
    $sql .= "ORDER BY t.id ";
		$rs = $db->Execute($sql);

    $row = 1;
    if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {
      $worksheet_open_status->setRow($row, 12);
      $custom_fields = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $rs->fields['id']);
//print "<pre>";
//print_r($custom_fields);
//print "</pre>";
      // Due Date, SLA, Date Recived, RM Name, RM Employee ID, Topic, Staff, New Hire, Notes/Email Body
      
      // Due Date Column 0
      $due_date_int = intval($custom_fields[$rs->fields['id']][1]);
      if ($due_date_int) {
        $ticket_due_date = date("n/j/y", $due_date_int);
      } else {
        $ticket_due_date = "";
      }
      $worksheet_open_status->writeString($row, 0, $ticket_due_date, $format_general);

      // SLA Column 1
      $worksheet_open_status->write($row, 1, $custom_fields[$rs->fields['id']][5], $format_general);

      // Overdue Column 2
      if (date("U", $due_date_int) >= time()) {
        $worksheet_open_status->write($row, 2, "No", $format_general);
      } else {
        $worksheet_open_status->write($row, 2, "Yes", $format_general);
      }

      // Date Recieved Column 3
      $ticket_created_date = date("n/j/y g:i:s A",intval($rs->fields['ticket_created_date']));
      $worksheet_open_status->writeString($row, 3, $ticket_created_date, $format_general);

      // RM Name Column 4
      $worksheet_open_status->write($row, 4, $custom_fields[$rs->fields['id']][3], $format_general);
      
      // RM Employee ID Column 5
      $worksheet_open_status->write($row, 5, $custom_fields[$rs->fields['id']][2], $format_general);
      
      // Topic / Request Type Column 6
      $worksheet_open_status->write($row, 6, $custom_fields[$rs->fields['id']][4], $format_general);
      
      // Staff Column 7
      $worksheet_open_status->write($row, 7, $custom_fields[$rs->fields['id']][8], $format_general);
      
      // New Hire Column 8
      $worksheet_open_status->write($row, 8, $custom_fields[$rs->fields['id']][6], $format_general);
      
      // Email Body Column 9
      $message_content = $rs->fields['content'];
      $worksheet_open_status->write($row, 9, trim($message_content), $format_general_nowrap);

      // Ticket Mask Column 10
      $mask = $rs->fields['mask'];
      $worksheet_open_status->write($row, 10, $mask, $format_general);

      $row++;
			$rs->MoveNext();
		}

    if ($RunFromCron) {
      $logger->info("[Answernet.com] " . $translate->_('answernet.er.metlife.metlife.done'));
      $logger->info("[Answernet.com] " . $translate->_('answernet.er.metlife.generating.dr.transaction.report'));
    } else {
      print $translate->_('answernet.er.metlife.metlife.done');
      print '<br>';
      print $translate->_('answernet.er.metlife.generating.dr.transaction.report');
    }

//SELECT t.mask, t.created_date ticket_created_date,
//m.created_date message_created_date, mc.content,  
//m.is_outgoing  
//FROM message m 
//INNER JOIN ticket t ON m.ticket_id = t.id 
//INNER JOIN address a ON m.address_id = a.id 
//INNER JOIN message_content mc on m.id = mc.message_id 
//WHERE t.team_id = 1721
//ORDER BY m.id;
    $sql = "SELECT t.id, t.mask, t.created_date ticket_created_date, ";
    $sql .= "m.created_date message_created_date, mc.content, m.is_outgoing ";
    $sql .= "FROM message m ";
    $sql .= "INNER JOIN ticket t ON m.ticket_id = t.id ";
    $sql .= "INNER JOIN address a ON m.address_id = a.id ";
    $sql .= "INNER JOIN message_content mc on m.id = mc.message_id ";
    $sql .= sprintf("WHERE m.created_date > %d AND m.created_date <= %d ", $start_ofday, $end_ofday);
    $sql .= "and t.team_id = 1721 ";
    $sql .= "ORDER BY t.id ";
    $rs = $db->Execute($sql);

    $row = 1;
    if(is_a($rs,'ADORecordSet'))
    while(!$rs->EOF) {
      $worksheet_transaction->setRow($row, 12);
      $custom_fields = DAO_CustomFieldValue::getValuesBySourceIds(ChCustomFieldSource_Ticket::ID, $rs->fields['id']);
      // Status, Due Date, SLA, SLA Age, Date Recived, RM Name, RM Employee ID, Topic, Staff, New Hire, Notes/Email Body

      if (intval($rs->fields['is_outgoing'])) {
        $worksheet_transaction->write($row, 0, "Recieved", $format_general);
      } else {
        $worksheet_transaction->write($row, 0, "Sent", $format_general);
      }

      // Due Date Column 1
      $due_date_int = intval($custom_fields[$rs->fields['id']][1]);
      if ($due_date_int) {
        $ticket_due_date = date("n/j/y", $due_date_int);
      } else {
        $ticket_due_date = "";
      }
      $worksheet_transaction->writeString($row, 1, $ticket_due_date, $format_general);

      // SLA Column 2
      $worksheet_transaction->write($row, 2, $custom_fields[$rs->fields['id']][5], $format_general);

      // Date Recieved Column 3
      $ticket_created_date = date("n/j/y g:i:s A",intval($rs->fields['ticket_created_date']));
      $worksheet_transaction->writeString($row, 3, $ticket_created_date, $format_general);

      // RM Name Column 4
      $worksheet_transaction->write($row, 4, $custom_fields[$rs->fields['id']][3], $format_general);
      
      // RM Employee ID Column 5
      $worksheet_transaction->write($row, 5, $custom_fields[$rs->fields['id']][2], $format_general);
      
      // Topic / Request Type Column 6
      $worksheet_transaction->write($row, 6, $custom_fields[$rs->fields['id']][4], $format_general);
      
      // Staff Column 7
      $worksheet_transaction->write($row, 7, $custom_fields[$rs->fields['id']][8], $format_general);
      
      // New Hire Column 8
      $worksheet_transaction->write($row, 8, $custom_fields[$rs->fields['id']][6], $format_general);
      
      // Email Body Column 9
      $message_content = $rs->fields['content'];
      $worksheet_transaction->write($row, 9, trim($message_content), $format_general_nowrap);

      // Ticket Mask Column 10
      $mask = $rs->fields['mask'];
      $worksheet_transaction->write($row, 10, $mask, $format_general);

      $row++;
      $rs->MoveNext();
    }

    if ($RunFromCron) {
      $logger->info("[Answernet.com] " . $translate->_('answernet.er.metlife.metlife.done'));
      $logger->info("[Answernet.com] " . $translate->_('answernet.er.metlife.generating.dr.monthly.report'));
    } else {
      print $translate->_('answernet.er.metlife.metlife.done');
      print '<br>';
      print $translate->_('answernet.er.metlife.generating.dr.monthly.report');
    }

/*
    print $translate->_('answernet.er.metlife.metlife.done');
    print '<br>';
    print $translate->_('answernet.er.metlife.generating.email.count');

    $worksheet_in_count->setRow(1, 24);
    $row_count = 2;
    foreach ($in_count_admin as $record) {
      $worksheet_in_count->write($row_count, 0, $record['email'], $format_general);
      $worksheet_in_count->write($row_count, 1, $record['count'], $format_general);
      $row_count++;
    }
    $worksheet_in_count->write(1, 0, 'Total Admin Email', $format_in_count_total);
    $worksheet_in_count->writeFormula(1, 1, "=SUM(B3:B".$row_count.")", $format_general);
    $row_count = 2;
    foreach ($in_count_other as $record) {
      $worksheet_in_count->write($row_count, 2, $record['email'], $format_general);
      $worksheet_in_count->write($row_count, 3, '', $format_general);
      $worksheet_in_count->write($row_count, 4, $record['count'], $format_general);
      $row_count++;
    }
    $worksheet_in_count->write(1, 2, 'Total Field Email', $format_in_count_total);
    $worksheet_in_count->write(1, 3, '', $format_in_count_total);
    $worksheet_in_count->writeFormula(1, 4, "=SUM(E3:E".$row_count.")", $format_general);
    // Grand Total
    $worksheet_in_count->write(1, 5, 'Grand Total Email', $format_general);
    $worksheet_in_count->writeFormula(1, 6, "=SUM(B2,E2)", $format_general);

    $worksheet_out_count->setRow(1, 24);
    $row_count = 2;
    foreach ($out_count_admin as $record) {
      $worksheet_out_count->write($row_count, 0, $record['email'], $format_general);
      $worksheet_out_count->write($row_count, 1, $record['count'], $format_general);
      $row_count++;
    }
    $worksheet_out_count->write(1, 0, 'Total Admin Email', $format_general);
    $worksheet_out_count->writeFormula(1, 1, "=SUM(B3:B".$row_count.")", $format_general);
    $row_count = 2;
    foreach ($out_count_other as $record) {
      $worksheet_out_count->write($row_count, 2, $record['email'], $format_general);
      $worksheet_out_count->write($row_count, 3, '', $format_general);
      $worksheet_out_count->write($row_count, 4, $record['count'], $format_general);
      $row_count++;
    }
    $worksheet_out_count->write(1, 2, 'Total Field Email', $format_general);
    $worksheet_out_count->write(1, 3, '', $format_out_count_total);
    $worksheet_out_count->writeFormula(1, 4, "=SUM(E3:E".$row_count.")", $format_general);
    // Grand Total
    $worksheet_out_count->write(1, 5, 'Grand Total Email', $format_general);
    $worksheet_out_count->writeFormula(1, 6, "=SUM(B2,E2)", $format_general);
*/
    $workbook->close();
    return $filename;
	}
};

class AnswernetMetlifeCron extends CerberusCronPageExtension {
  const EXTENSION_ID = 'answernet.er.metlife.id.cron';

  function run() {
    date_default_timezone_set('Etc/UTC');
    $run_date = $this->getParam('rundate', 0);
    $logger = DevblocksPlatform::getConsoleLog();
    $logger->info("[Answernet.com] Running Metlife DR report and emailing it.");

    $logger->info("[Answernet.com] run_date = ".$run_date);
    $logger->info("[Answernet.com] date('j') = ".date("j"));
      
    if (date("j") == $run_date) {
      return;
    }
    $this->setParam('rundate', date("j"));

    @ini_set('memory_limit','128M');
 
 
    $filename = AnswernetMetlifeReportGroupReportDR::AnswernetMetlifeReportDRReport(1);
    $full_filename = getcwd().'/storage/answernet/'.$filename;
    
    $logger->info("[Answernet.com] filename = ".$filename);
    $logger->info("[Answernet.com] full_filename = ".$full_filename);

    self::SendReport($full_filename, $filename);

    $logger->info("[Answernet.com] Finished processing Metlife DR report.");
  }
 
  function configure($instance) {
    $tpl = DevblocksPlatform::getTemplateService();
    $tpl->cache_lifetime = "0";
    $tpl_path = dirname(dirname(__FILE__)) . '/templates/';
    $tpl->assign('path', $tpl_path);

    $tpl->assign('answernet_email01', $this->getParam('answernet_email01', ''));
    $tpl->assign('answernet_email02', $this->getParam('answernet_email02', ''));
    $tpl->assign('answernet_email03', $this->getParam('answernet_email03', ''));
    $tpl->assign('answernet_email04', $this->getParam('answernet_email04', ''));
    $tpl->assign('answernet_email05', $this->getParam('answernet_email05', ''));

    $tpl->display($tpl_path . 'cron.tpl');
  }
 
  function saveConfigurationAction() {
    @$answernet_email01 = DevblocksPlatform::importGPC($_POST['answernet_email01'],'string','');
    @$answernet_email02 = DevblocksPlatform::importGPC($_POST['answernet_email02'],'string','');
    @$answernet_email03 = DevblocksPlatform::importGPC($_POST['answernet_email03'],'string','');
    @$answernet_email04 = DevblocksPlatform::importGPC($_POST['answernet_email04'],'string','');
    @$answernet_email05 = DevblocksPlatform::importGPC($_POST['answernet_email05'],'string','');
    $this->setParam('answernet_email01', $answernet_email01);
    $this->setParam('answernet_email02', $answernet_email02);
    $this->setParam('answernet_email03', $answernet_email03);
    $this->setParam('answernet_email04', $answernet_email04);
    $this->setParam('answernet_email05', $answernet_email05);
  }

  function SendReport($full_filename, $filename) {
    $logger = DevblocksPlatform::getConsoleLog();

    $from = 'support@myfirstperson.com';
    $personal = 'First Person';
    $subject = 'DR Metlife Report Generated on ' . date("n/j/y");;
    $mail_headers = array();
    $mail_headers['X-CerberusCompose'] = '1';
    $toList = NULL;
    $abort = true;

    @$answernet_email01 = $this->getParam('answernet_email01', NULL);    
    @$answernet_email02 = $this->getParam('answernet_email02', NULL);    
    @$answernet_email03 = $this->getParam('answernet_email03', NULL);    
    @$answernet_email04 = $this->getParam('answernet_email04', NULL);    
    @$answernet_email05 = $this->getParam('answernet_email05', NULL);    

    if ($answernet_email01 != '') {
      $logger->info("[Answernet.com] answernet_email01 = ".$answernet_email01);
      $toList = $answernet_email01;
      $abort = false;
    }
    if ($answernet_email02 != '') {
      $logger->info("[Answernet.com] answernet_email02 = ".$answernet_email02);
      if (!is_null($toList)) { $toList .= ","; }
      $toList .= $answernet_email02;
      $abort = false;
    }
    if ($answernet_email03 != '') {
      $logger->info("[Answernet.com] answernet_email03 = ".$answernet_email03);
      if (!is_null($toList)) { $toList .= ","; }
      $toList .= $answernet_email03;
      $abort = false;
    }
    if ($answernet_email04 != '') {
      $logger->info("[Answernet.com] answernet_email04 = ".$answernet_email04);
      if (!is_null($toList)) { $toList .= ","; }
      $toList .= $answernet_email04;
      $abort = false;
    }
    if ($answernet_email05 != '') {
      $logger->info("[Answernet.com] answernet_email05 = ".$answernet_email05);
      if (!is_null($toList)) { $toList .= ","; }
      $toList .= $answernet_email05;
      $abort = false;
    }

    if ($abort) {
      $logger->info("[Answernet.com] Aborting email send.");
      return;
    }
    $logger->info("[Answernet.com] toList = ".$toList);

    $files['name']['0'] = $filename;
    $files['type']['0'] = 'application/vnd.ms-excel';
    $files['tmp_name']['0'] = $full_filename;

    $properties = array(
      'team_id' => 1771,
//      'team_id' => 584,
      'content' => "Metlife DR report attached",
      'subject' => $subject,
      'closed' => 0,
      'agent_id' => 0,
      'to' => $toList,
      'files' => $files
      );
    $ticket_id = CerberusMail::compose($properties);
    $logger->info("[Answernet.com] ticket_id = ".$ticket_id);
    return;
  }
};

endif;
