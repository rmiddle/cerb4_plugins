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
		$db = DevblocksPlatform::getDatabaseService();
		$translate = DevblocksPlatform::getTranslationService();
    $url = DevblocksPlatform::getUrlService();
    $ticket_fields = DAO_CustomField::getAll();

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
    print '<br><br><br>';
    
    $start_ofday = mktime(0, 0, 0, date("m", $start_time), date("d", $start_time), date("Y", $start_time));
    $end_ofday = $start_ofday + 86400;
    print $translate->_('answernet.er.metlife.report.dr.date.from.text');
    print date(" Y-m-d H:i:s ", $start_ofday);    
    print $translate->_('answernet.er.metlife.report.dr.date.to.text');
    print date(" Y-m-d H:i:s T", $end_ofday);    
    print '<br>';
    print $translate->_('answernet.er.metlife.generate.report');

    $filename = "report-metlife-dr-" . date("Ymd", $start_time) . ".xls";
    print '<br>';
    print $translate->_('answernet.er.metlife.generating');

		$full_filename = getcwd().'/storage/answernet/'.$filename;
    $href_filename = $url->write('storage/answernet/'.$filename, true);

    // Create new Excel Spreadsheet.
    $workbook = new Spreadsheet_Excel_Writer($full_filename);

    // Create monthly Tab and set Column Width and Row Hight.
    $worksheet_monthly =& $workbook->addWorksheet('Month DR Summary');
    $worksheet_monthly->setColumn(0, 0, $radius*2.46);
    $worksheet_monthly->setColumn(1, 1, $radius*.47);
    $worksheet_monthly->setColumn(2, 2, $radius*.63);
    $worksheet_monthly->setColumn(3, 3, $radius*.69);
    $worksheet_monthly->setColumn(4, 4, $radius*.69);
    $worksheet_monthly->setColumn(5, 5, $radius*.69);
//    $worksheet_monthly->setRow(0, 56);

    // Create Open Status Tab and set Column Width and Row Hight.
    $worksheet_open_status =& $workbook->addWorksheet('Open DR Report');
    $worksheet_open_status->setColumn(0, 0, $radius*0.85);
    $worksheet_open_status->setColumn(1, 1, $radius*0.35);
    $worksheet_open_status->setColumn(2, 2, $radius*0.61);
    $worksheet_open_status->setColumn(3, 3, $radius*1.00);
    $worksheet_open_status->setColumn(4, 4, $radius*0.82);
    $worksheet_open_status->setColumn(5, 5, $radius*1.16);
    $worksheet_open_status->setColumn(6, 6, $radius*2.40);
    $worksheet_open_status->setColumn(7, 8, $radius*0.87);
    $worksheet_open_status->setColumn(9, 9, $radius*3.28);
    $worksheet_open_status->setColumn(10, 10, $radius*1.34);
//    $worksheet_open_status->setRow(0, 28);
//    $worksheet_open_status->setRow(2, 32);

    // Create Open Status Tab and set Column Width and Row Hight.
    $worksheet_transaction=& $workbook->addWorksheet('Transaction Report');
    $worksheet_transaction->setColumn(0, 0, $radius*0.70);
    $worksheet_transaction->setColumn(1, 1, $radius*0.85);
    $worksheet_transaction->setColumn(2, 3, $radius*0.35);
    $worksheet_transaction->setColumn(4, 4, $radius*1.00);
    $worksheet_transaction->setColumn(5, 5, $radius*0.82);
    $worksheet_transaction->setColumn(6, 6, $radius*1.16);
    $worksheet_transaction->setColumn(7, 7, $radius*2.40);
    $worksheet_transaction->setColumn(8, 9, $radius*0.87);
    $worksheet_transaction->setColumn(10, 10, $radius*3.28);
    $worksheet_transaction->setColumn(11, 11, $radius*1.34);
//    $worksheet_open_status->setRow(0, 28);
//    $worksheet_open_status->setRow(2, 32);

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

    // Added headers since they never change in the Metics Group.
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

// Added headers since they never change in the acd in Group.
    $worksheet_open_status->write(0, 0, 'Due Date', $format_general_title);
    $worksheet_open_status->write(0, 1, 'SLA', $format_general_title);
    $worksheet_open_status->write(0, 2, 'SLA Age', $format_general_title);
    $worksheet_open_status->write(0, 3, 'Date Received', $format_general_title);
    $worksheet_open_status->write(0, 4, 'RM Name', $format_general_title);
    $worksheet_open_status->write(0, 5, 'RM Employee id', $format_general_title);
    $worksheet_open_status->write(0, 6, 'Request Type', $format_general_title);
    $worksheet_open_status->write(0, 7, 'MetLife Staff', $format_general_title);
    $worksheet_open_status->write(0, 8, 'New Hire', $format_general_title);
    $worksheet_open_status->write(0, 9, 'Nates (email body)', $format_general_title);
    $worksheet_open_status->write(0, 10, 'Ticket Mask', $format_general_title);

// Added headers since they never change in the acd in Group.
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

    print $translate->_('answernet.er.metlife.metlife.done');
    print '<br>';
    print $translate->_('answernet.er.metlife.generating.dr.daily.report');

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
      // Due Date, SLA, Date Recived, RM Name, RM Employee ID, Topic, Staff, New Hire, Notes/Email Body
      
      // Due Date Column 0

      // SLA Column 1

      // SLA Done

      // Date Recieved Column 3
      $ticket_created_date = intval($rs->fields['ticket_created_date']);
      $worksheet_open_status->write($row, 3, $ticket_created_date, $format_general);

      // RM Name Column 4
      
      // RM Employee ID Column 5
      
      // Topic / Request Type Column 6
      
      // Staff Column 7
      
      // New Hire Column 8
      
      // Email Body Column 9
      $message_content = $rs->fields['content'];
      $worksheet_open_status->write($row, 9, trim($message_content), $format_general_nowrap);

      // Ticket Mask Column 10
      $mask = $rs->fields['mask'];
      $worksheet_open_status->write($row, 10, $mask, $format_general);

      $row++;
			$rs->MoveNext();
		}

    print $translate->_('answernet.er.metlife.metlife.done');
    print '<br>';
    print $translate->_('answernet.er.metlife.generating.dr.transaction.report');

//SELECT t.mask, t.created_date ticket_created_date,
//m.created_date message_created_date, mc.content,  
//m.is_outgoing  
//FROM message m 
//INNER JOIN ticket t ON m.ticket_id = t.id 
//INNER JOIN address a ON m.address_id = a.id 
//INNER JOIN message_content mc on m.id = mc.message_id 
//WHERE t.team_id = 1721
//ORDER BY m.id;
    $sql = "SELECT t.mask, t.created_date ticket_created_date, ";
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
      // Status, Due Date, SLA, SLA Age, Date Recived, RM Name, RM Employee ID, Topic, Staff, New Hire, Notes/Email Body

      if (intval($rs->fields['is_outgoing'])) {
        $worksheet_transaction->write($row, 0, "Recieved", $format_general);
      } else {
        $worksheet_transaction->write($row, 0, "Sent", $format_general);
      }

      // Due Date Column 1

      // SLA Column 2

      // Date Recieved Column 3
      $ticket_created_date = date("d M Y H:i:s'",intval($rs->fields['ticket_created_date']));
      $worksheet_transaction->write($row, 3, $ticket_created_date, $format_general);

      // RM Name Column 4
      
      // RM Employee ID Column 5
      
      // Topic / Request Type Column 6
      
      // Staff Column 7
      
      // New Hire Column 8
      
      // Email Body Column 9
      $message_content = $rs->fields['content'];
      $worksheet_transaction->write($row, 9, trim($message_content), $format_general_nowrap);

      // Ticket Mask Column 10
      $mask = $rs->fields['mask'];
      $worksheet_transaction->write($row, 10, $mask, $format_general);

      $row++;
      $rs->MoveNext();
    }

    print $translate->_('answernet.er.metlife.metlife.done');
    print '<br>';
    print $translate->_('answernet.er.metlife.generating.dr.monthly.report');

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
    print $translate->_('answernet.er.metlife.metlife.done');
    print '<br>';
    print $translate->_('answernet.er.metlife.generating');
		print $translate->_('answernet.er.metlife.metlife.done');
		print '<br><br>';
		print '<b><a href=' . $href_filename . '>' . $translate->_('answernet.er.metlife.download.xls') . '</a></b>';
		print '<br><br>';
	}

};
endif;

?>
