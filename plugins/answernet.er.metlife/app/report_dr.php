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
    $workers = DAO_Worker::getAll();
    $radius = 12;
    $start_time = 0;

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
    print $translate->_('answernet.er.metlife.report.dr.daily.text');
    print date("F j, Y", $start_time);
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
//    $worksheet_monthly->setColumn(0, 0, $radius*1.71);
//    $worksheet_monthly->setColumn(0, 0, $radius*.50);
//    $worksheet_monthly->setRow(0, 56);

    // Create ACD Calls(Inbound) Tab and set Column Width and Row Hight.
    $worksheet_daily =& $workbook->addWorksheet('Daily Report)');
    $worksheet_daily->setColumn(0, 1, $radius*0.78);
    $worksheet_daily->setColumn(2, 2, $radius*1.05);
    $worksheet_daily->setColumn(3, 3, $radius*1.23);
    $worksheet_daily->setColumn(4, 4, $radius*1.11);
    $worksheet_daily->setColumn(5, 5, $radius*1.15);
    $worksheet_daily->setColumn(6, 6, $radius*2.00);
    $worksheet_daily->setColumn(7, 7, $radius*0.78);
    $worksheet_daily->setColumn(8, 8, $radius*2.00);
    $worksheet_daily->setColumn(9, 9, $radius*0.78);
    $worksheet_daily->setRow(0, 28);
    $worksheet_daily->setRow(2, 32);

    // Formats used thoughout the workbook.
    $format_general =& $workbook->addFormat();
    $format_general->setBorder(1);
    $format_general->setHAlign('center');
    $format_general->setTextWrap();

    $format_general_title =& $workbook->addFormat();
    $format_general_title->setBorder(1);
    $format_general_title->setHAlign('center');
    $format_general_title->setTextWrap();

    $format_general_nowrap =& $workbook->addFormat();
    $format_general_nowrap->setBorder(1);

    // Setup templating for the formating of certain cells in the Monthly Group.
    $format_monthly_title =& $workbook->addFormat();
    $format_monthly_title->setBorder(1);
    $format_monthly_title->setBold();
    $format_monthly_title->setColor(9);
    $format_monthly_title->setFgColor(32);
    $format_monthly_title->setHAlign('center');
    $format_monthly_title->setVAlign('vjustify');
    $format_monthly_title->setVAlign('vcenter');
    $format_monthly_title->setTextWrap();

    // Added headers since they never change in the Metics Group.
    $worksheet_monthly->write(0, 0, 'Week Range', $format_monthly_title);
    $worksheet_monthly->write(0, 1, 'Inbnd Field Emails', $format_monthly_title);
    $worksheet_monthly->write(0, 2, 'Inbnd Admin Emails', $format_monthly_title);
    $worksheet_monthly->write(0, 3, 'Weekly Total Inbnd', $format_monthly_title);
    $worksheet_monthly->write(0, 4, 'Outbnd Field Emails', $format_monthly_title);
    $worksheet_monthly->write(0, 5, 'Outbnd Admin Emails', $format_monthly_title);
    $worksheet_monthly->write(0, 6, 'Weekly Total Outbnd', $format_monthly_title);
    $worksheet_monthly->write(0, 7, 'Avg time to respond (hrs)', $format_monthly_title);
    $worksheet_monthly->write(0, 8, ' ', $format_monthly_title);
    $worksheet_monthly->write(0, 9, ' ', $format_monthly_title);
    $worksheet_monthly->write(0, 10, ' ', $format_monthly_title);
    $worksheet_monthly->write(0, 11, ' ', $format_monthly_title);
    $worksheet_monthly->write(0, 12, ' ', $format_monthly_title);
    $worksheet_monthly->write(0, 13, ' ', $format_monthly_title);
    $worksheet_monthly->write(0, 14, ' ', $format_monthly_title);

    $month_text = date("F-y", $start_time);
    $worksheet_monthly->write(1, 0, $month_text, $format_general);
    $worksheet_monthly->write(5, 0, 'Grand Total', $format_general);
    $worksheet_monthly->write(6, 0, 'Weekly Averages', $format_general);
    $worksheet_monthly->write(7, 0, 'Daily Averages', $format_general);
    $worksheet_monthly->write(8, 0, '%', $format_general);

    // Added headers since they never change in the acd in Group.
    $worksheet_daily->write(0, 0, 'MetLife / '.$group_text, $format_general_title);
    $worksheet_daily->write(0, 1, '', $format_general_title);
    $worksheet_daily->write(0, 2, '', $format_general_title);
    $worksheet_daily->write(0, 3, '', $format_general_title);
    $worksheet_daily->write(0, 4, '', $format_general_title);
    $worksheet_daily->write(0, 5, '', $format_general_title);
    $worksheet_daily->write(0, 6, 'WEEKLY TOTALS', $format_general_title);
    $worksheet_daily->write(0, 7, '', $format_general_title);
    $worksheet_daily->write(0, 8, '', $format_general_title);
    $worksheet_daily->write(0, 9, '', $format_general_title);
    $worksheet_daily->write(1, 0, $week_range_text, $format_general_title);
    $worksheet_daily->write(1, 1, '', $format_general_title);
    $worksheet_daily->write(1, 2, '', $format_general_title);
    $worksheet_daily->write(1, 3, '', $format_general_title);
    $worksheet_daily->write(1, 4, '', $format_general_title);
    $worksheet_daily->write(1, 5, '', $format_general_title);
    $worksheet_daily->write(2, 0, 'Date', $format_general_title3);
    $worksheet_daily->write(2, 1, 'Call Times', $format_general_title3);
    $worksheet_daily->write(2, 2, 'Agent Talk', $format_general_title3);
    $worksheet_daily->write(2, 3, '(MIN.SEC) Hold time', $format_general_title3);
    $worksheet_daily->write(2, 4, '(MIN.SEC) Patch Time', $format_general_title3);
    $worksheet_daily->write(2, 5, 'ANI', $format_general_title3);
    $worksheet_daily->write(1, 6, 'Weekly Total Calls', format_general_title);
    $worksheet_daily->writeFormula(1, 7, "=count(A1:A1000)", $format_general_title);
    $worksheet_daily->write(1, 8, 'Weekly Average Patch Time', format_general_title);
    $worksheet_daily->writeFormula(1, 9, "=SUM(B2,E2)", $format_general_title);
    $worksheet_daily->write(2, 6, 'Weekly Agent Talk Time', format_general_title);
    $worksheet_daily->writeFormula(2, 7, "=SUM(B2,E2)", $format_general_title);
    $worksheet_daily->write(2, 8, 'Weekly Average Hold Time', format_general_title);
    $worksheet_daily->writeFormula(2, 9, "=SUM(B2,E2)", $format_general_title);


    print $translate->_('answernet.er.metlife.metlife.done');
    print '<br>';
    print $translate->_('answernet.er.metlife.generating.email.detail');

		$groups = DAO_Group::getAll();
		$buckets = DAO_Bucket::getAll();

    $sql = "SELECT t.mask, a.email, m.address_id, a.contact_org_id, ";
    $sql .= "t.created_date ticket_created_date, t.team_id, ";
    $sql .= "m.created_date message_created_date, mc.content, ";
    $sql .= "mh.header_value message_subject, m.worker_id, ";
    $sql .= "m.is_outgoing, mh_to.header_value outbound_email, t.team_id ";
    $sql .= "FROM message m ";
    $sql .= "INNER JOIN ticket t ON m.ticket_id = t.id ";
    $sql .= "INNER JOIN address a ON m.address_id = a.id ";
    $sql .= "INNER JOIN message_content mc on m.id = mc.message_id ";
    $sql .= "INNER JOIN message_header mh on m.id = mh.message_id ";
    $sql .= "and mh.header_name = 'subject' ";
    $sql .= "INNER JOIN message_header mh_to on m.id = mh_to.message_id ";
    $sql .= "and mh_to.header_name = 'to' ";
    $sql .= sprintf("WHERE m.created_date > %d AND m.created_date <= %d ", $start_time, $end_time);
    // Set abouve based on group selected.
    $sql .= $group_sql;
    $sql .= "ORDER BY m.id ";
		$rs = $db->Execute($sql);

    $row_inbound = 2;
    $row_outbound = 2;
    $in_count_admin = array();
    $in_count_other = array();
    $out_count_admin = array();
    $out_count_other = array();

		if(is_a($rs,'ADORecordSet'))
		while(!$rs->EOF) {

			$mask = $rs->fields['mask'];
      $ticket_created_date = intval($rs->fields['ticket_created_date']);
      $team_id = intval($rs->fields['team_id']);
      // Date Format Month/Day/Year Hour:Min:Sec AM/PM
      $message_created_date = date("n/j/y g:i:s A",intval($rs->fields['message_created_date']));
      $message_content = $rs->fields['content'];
      $message_subject = $rs->fields['message_subject'];
      $worker_id = $rs->fields['worker_id'];
      $is_outgoing = $rs->fields['is_outgoing'];
      if ($team_id == 756) {
        $team_text = 'First Person';
      }
      elseif ($team_id == 782) {
        $team_text = 'iDesign';
      }
      else {
        $team_text = 'Error';
      }
      if($worker_id) {
        $worker_name = $workers[$worker_id]->first_name;
      }
      else {
        $worker_name = "";
      }
      if ($is_outgoing) {
        $outbound_email = $rs->fields['outbound_email'];
        $to = array();
        $to = CerberusParser::parseRfcAddress($outbound_email);
        @$toAddress = $to[0]->mailbox.'@'.$to[0]->host;
        $toAddressInst = CerberusApplication::hashLookupAddress($toAddress, true);
        $address_id = $toAddressInst->id;
        $contact_org_id = $toAddressInst->contact_org_id;
        $email = $toAddressInst->email;
      } else {
        $address_id = $rs->fields['address_id'];
        $contact_org_id = $rs->fields['contact_org_id'];
        $email = $rs->fields['email'];
      }
      if ($is_outgoing) {
        $worksheet_outbound->setRow($row_outbound, 12);
        $worksheet_outbound->write($row_outbound, 0, $email, $format_general);
        $worksheet_outbound->write($row_outbound, 1, "", $format_general);
        $worksheet_outbound->write($row_outbound, 2, "", $format_general);
        $worksheet_outbound->write($row_outbound, 3, $message_created_date, $format_general);
        $worksheet_outbound->write($row_outbound, 4, $mask, $format_general);
        $worksheet_outbound->write($row_outbound, 5, trim($message_subject), $format_general_nowrap);
        $worksheet_outbound->write($row_outbound, 6, trim(strip_tags($message_content)));
        $worksheet_outbound->writeString($row_outbound, 10, $worker_name, $format_general);
        $worksheet_outbound->write($row_outbound, 11, $team_text, $format_general);
        $row_outbound++;
      }
      else {
        $worksheet_inbound->setRow($row_inbound, 12);
        $worksheet_inbound->write($row_inbound, 0, $email, $format_general);
        $worksheet_inbound->write($row_inbound, 1, "", $format_general);
        $worksheet_inbound->write($row_inbound, 2, "", $format_general);
        $worksheet_inbound->write($row_inbound, 3, $message_created_date, $format_general);
        $worksheet_inbound->write($row_inbound, 4, $mask, $format_general);
        $worksheet_inbound->write($row_inbound, 5, trim($message_subject), $format_general_nowrap);
        $worksheet_inbound->writeString($row_inbound, 6, trim(strip_tags($message_content)));
        $worksheet_inbound->write($row_inbound, 10, $team_text, $format_general);
        $row_inbound++;
      }

      if ($is_outgoing) {
        if ($contact_org_id == 1) {
          if(!isset($out_count_admin[$address_id]['count'])) {
            $out_count_admin[$address_id]['email'] = $email;
            $out_count_admin[$address_id]['count'] = 1;
          }
          else {
            $out_count_admin[$address_id]['count']++;
          }
        }
        else {
          if(!isset($out_count_other[$address_id]['count'])) {
            $out_count_other[$address_id]['email'] = $email;
            $out_count_other[$address_id]['count'] = 1;
          }
          else {
            $out_count_other[$address_id]['count']++;
          }
        }
      }
      else {
        if ($contact_org_id == 1) {
          if(!isset($in_count_admin[$address_id]['count'])) {
            $in_count_admin[$address_id]['email'] = $email;
            $in_count_admin[$address_id]['count'] = 1;
          }
          else {
            $in_count_admin[$address_id]['count']++;
          }
        }
        else {
          if(!isset($in_count_other[$address_id]['count'])) {
            $in_count_other[$address_id]['email'] = $email;
            $in_count_other[$address_id]['count'] = 1;
          }
          else {
            $in_count_other[$address_id]['count']++;
          }
        }
      }

			$rs->MoveNext();
		}

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

    $workbook->close();
    print $translate->_('answernet.er.metlife.metlife.done');
    print '<br>';
    print $translate->_('ranswernet.er.metlife.generating');
		print $translate->_('answernet.er.metlife.metlife.done');
		print '<br><br>';
		print '<b><a href=' . $href_filename . '>' . $translate->_('answernet.er.metlife.download.xls') . '</a></b>';
		print '<br><br>';
	}

};
endif;

?>
