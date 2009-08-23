<?php

require_once 'Spreadsheet/Excel/Writer.php';

class EmailResponseReportsPlugin extends DevblocksPlugin {
	function load(DevblocksPluginManifest $manifest) {
	}
};

if (class_exists('DevblocksTranslationsExtension',true)):
	class EmailResponseTranslations extends DevblocksTranslationsExtension {
		function __construct($manifest) {
			parent::__construct($manifest);
		}

		function getTmxFile() {
			return dirname(dirname(__FILE__)) . '/strings.xml';
		}
	};
endif;

class EmailResponseReportGroups extends Extension_ReportGroup {
	function __construct($manifest) {
		parent::__construct($manifest);
	}
};

if (class_exists('Extension_Report',true)):
class EmailResponseReportMetLife extends Extension_Report {
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

    $metlife_choices ['fp']['group'] = 'fp';
    $metlife_choices ['fp']['text'] = 'First Person';
    $metlife_choices ['iDesign']['group'] = 'iDesign';
    $metlife_choices ['iDesign']['text'] = 'iDesign';

    $tpl->assign('metlife_choices', $metlife_choices);

    $date_var = strtotime("Last Monday");
    for ($i = 1; $i <= 10; $i++) {
      $week_choices[$i]['time'] = $date_var;
      $week_choices[$i]['text'] = "Week of ".date("W: n/j/y", $date_var).' - '.date("n/j/y", $date_var+518400);
      $date_var -= 604800;
    }
    $tpl->assign('week_choices', $week_choices);

		$tpl->display('file:' . $this->tpl_path . '/report_metlife.tpl');
	}

	function getEmailResponseReportMetLifeAction() {
		$db = DevblocksPlatform::getDatabaseService();
		$translate = DevblocksPlatform::getTranslationService();
    $url = DevblocksPlatform::getUrlService();
    $workers = DAO_Worker::getAll();
    $radius = 12;

		// Security
		if(null == ($active_worker = CerberusApplication::getActiveWorker()))
			die($translate->_('common.access_denied'));

		// import dates from form

		@$start_time = DevblocksPlatform::importGPC($_REQUEST['start'],'string','');
    @$group = DevblocksPlatform::importGPC($_REQUEST['group'],'string','');

		if (empty($start_time) || !is_numeric($start_time)) {
      return;
    }

    if (empty($group)) {
      return;
    }

    $end_time = $start_time + 604800;
    print $translate->_('reports.ui.group.emailresponse.answernet.week.number');
    print date("W", $start_time);
    print '<br>';
    print $translate->_('reports.ui.group.emailresponse.answernet.metlife.generating');

    switch ($group) {
      case "All":
          $filename = "report-metlife-week-" . date("W", $start_time) . ".xls";
          $group_sql = "and (t.team_id = 756 or t.team_id = 782) ";
          print " Group: ALL ";
          $group_text = "All";
          break;
      case "fp":
          $filename = "report-metlife-first-person-week-" . date("W", $start_time) . ".xls";
          $group_sql = "and t.team_id = 756 ";
          print " Group: First Person ";
          $group_text = "First Person";
          break;
      case "iDesign":
          $filename = "report-metlife-iDesign-week-" . date("W", $start_time) . ".xls";
          $group_sql = "and team_id = 782 ";
          print " Group: iDesign ";
          $group_text = "iDesign";
          break;
      default:
          print "Error: Group not set: ".$group;
          return;
    }
    print '<br>';
    print $translate->_('reports.ui.group.emailresponse.answernet.metlife.generating.template');

		$full_filename = getcwd().'/storage/answernet/'.$filename;
    $href_filename = $url->write('storage/answernet/'.$filename, true);

    $week_range_text = "Week # " . date("W - n/j/y", $start_time) . " - " . date("n/j/y", $start_time+518400);

    // Create new Excel Spreadsheet.
    $workbook = new Spreadsheet_Excel_Writer($full_filename);

    // Create metrics Tab and set Column Width and Row Hight.
    $worksheet_metrics =& $workbook->addWorksheet('Weekly Metrics');
    $worksheet_metrics->setColumn(0, 0, $radius*1.71);
    $worksheet_metrics->setColumn(0, 0, $radius*.50);
    $worksheet_metrics->setRow(0, 56);

    // Create ACD Calls(Inbound) Tab and set Column Width and Row Hight.
    $worksheet_acd_in =& $workbook->addWorksheet('ACD calls(Inbound)');
    $worksheet_acd_in->setColumn(0, 1, $radius*0.78);
    $worksheet_acd_in->setColumn(2, 2, $radius*1.05);
    $worksheet_acd_in->setColumn(3, 3, $radius*1.23);
    $worksheet_acd_in->setColumn(4, 4, $radius*1.11);
    $worksheet_acd_in->setColumn(5, 5, $radius*1.15);
    $worksheet_acd_in->setColumn(6, 6, $radius*2.00);
    $worksheet_acd_in->setColumn(7, 7, $radius*0.78);
    $worksheet_acd_in->setColumn(8, 8, $radius*2.00);
    $worksheet_acd_in->setColumn(9, 9, $radius*0.78);
    $worksheet_acd_in->setRow(0, 28);
    $worksheet_acd_in->setRow(2, 32);

    // Create ACD Calls(Outbound) Tab and set Column Width and Row Hight.
    $worksheet_acd_out =& $workbook->addWorksheet('ACD calls(Outbound)');

    // Create Phone Tickets Tab and set Column Width and Row Hight.
    $worksheet_phone_tickets =& $workbook->addWorksheet('Phone Tickets');

    // Create Email Tickets Tab and set Column Width and Row Hight.
    $worksheet_email_tickets =& $workbook->addWorksheet('Email Tickets');

    // Create Email Tickets Tab and set Column Width and Row Hight.
    $worksheet_call_count =& $workbook->addWorksheet('Call Count');

    // Create Inbound Count Tab and set Column Width and Row Hight.
    $worksheet_in_count =& $workbook->addWorksheet('Inbound Email Count');
    $worksheet_in_count->setColumn(0, 0, $radius*2.87);
    $worksheet_in_count->setColumn(1, 1, $radius*0.66);
    $worksheet_in_count->setColumn(2, 2, $radius*2.87);
    $worksheet_in_count->setColumn(3, 3, $radius*0.62);
    $worksheet_in_count->setColumn(4, 4, $radius*0.66);
    $worksheet_in_count->setColumn(5, 5, $radius*2.87);
    $worksheet_in_count->setColumn(6, 6, $radius*0.66);
    $worksheet_in_count->setRow(0, 32);
    $worksheet_in_count->freezePanes(array(2, 0, 2, 0));

    // Create Outbound Count Tab and set Column Width and Row Hight.
    $worksheet_out_count =& $workbook->addWorksheet('Outbound Email Count');
    $worksheet_out_count->setColumn(0, 0, $radius*2.87);
    $worksheet_out_count->setColumn(1, 1, $radius*0.66);
    $worksheet_out_count->setColumn(2, 2, $radius*2.87);
    $worksheet_out_count->setColumn(3, 3, $radius*0.62);
    $worksheet_out_count->setColumn(4, 4, $radius*0.66);
    $worksheet_out_count->setColumn(5, 5, $radius*2.87);
    $worksheet_out_count->setColumn(6, 6, $radius*0.66);
    $worksheet_out_count->setRow(0, 32);
    $worksheet_out_count->freezePanes(array(2, 0, 2, 0));

    // Create Inbound Tab and set Column Width and Row Hight.
    $worksheet_inbound =& $workbook->addWorksheet('Inbound Emails');
    $worksheet_inbound->setColumn(0, 0, $radius*2.78);
    $worksheet_inbound->setColumn(1, 1, $radius*1.55);
    $worksheet_inbound->setColumn(2, 2, $radius*0.72);
    $worksheet_inbound->setColumn(3, 4, $radius*1.51);
    $worksheet_inbound->setColumn(5, 5, $radius*2.76);
    $worksheet_inbound->setColumn(6, 9, $radius*2.22);
    $worksheet_inbound->setColumn(10, 10, $radius*0.83);
    $worksheet_inbound->setRow(0, 36);
    $worksheet_inbound->freezePanes(array(2, 0, 2, 0));

    // Create Outbound Tab and set Column Width and Row Hight.
    $worksheet_outbound =& $workbook->addWorksheet('Outbound Emails');
    $worksheet_outbound->setColumn(0, 0, $radius*2.78);
    $worksheet_outbound->setColumn(1, 1, $radius*1.55);
    $worksheet_outbound->setColumn(2, 2, $radius*0.72);
    $worksheet_outbound->setColumn(3, 4, $radius*1.51);
    $worksheet_outbound->setColumn(5, 5, $radius*2.76);
    $worksheet_outbound->setColumn(6, 9, $radius*2.22);
    $worksheet_outbound->setColumn(10, 10, $radius*1.20);
    $worksheet_outbound->setColumn(11, 11, $radius*0.83);
    $worksheet_outbound->setRow(0, 36);
    $worksheet_outbound->freezePanes(array(2, 0, 2, 0));

    // Formats used thoughout the workbook.
    $format_general =& $workbook->addFormat();
    $format_general->setBorder(1);
    $format_general->setHAlign('center');
    $format_general->setTextWrap();

    $format_general_nowrap =& $workbook->addFormat();
    $format_general_nowrap->setBorder(1);

    // Setup templating for the formating of certain cells in the Metics Group.
    $format_metrics_title =& $workbook->addFormat();
    $format_metrics_title->setBorder(1);
    $format_metrics_title->setBold();
    $format_metrics_title->setColor(9);
    $format_metrics_title->setFgColor(32);
    $format_metrics_title->setHAlign('center');
    $format_metrics_title->setVAlign('vjustify');
    $format_metrics_title->setVAlign('vcenter');
    $format_metrics_title->setTextWrap();

    $format_metrics_title2 =& $workbook->addFormat();
    $format_metrics_title2->setBorder(1);
    $format_metrics_title2->setBold();
    $format_metrics_title2->setColor(8);
    $format_metrics_title2->setFgColor(43);
    $format_metrics_title2->setHAlign('center');
    $format_metrics_title2->setVAlign('vjustify');
    $format_metrics_title2->setVAlign('vcenter');
    $format_metrics_title2->setTextWrap();

    $format_metrics_weekly =& $workbook->addFormat();
    $format_metrics_weekly->setBorder(1);
    $format_metrics_weekly->setBold();
    $format_metrics_weekly->setColor(8);
    $format_metrics_weekly->setFgColor(29);
    $format_metrics_weekly->setHAlign('center');
    $format_metrics_weekly->setVAlign('vjustify');
    $format_metrics_weekly->setVAlign('vcenter');
    $format_metrics_weekly->setTextWrap();

    $format_metrics_daily =& $workbook->addFormat();
    $format_metrics_daily->setBorder(1);
    $format_metrics_daily->setBold();
    $format_metrics_daily->setColor(8);
    $format_metrics_daily->setFgColor(29);
    $format_metrics_daily->setHAlign('center');
    $format_metrics_daily->setVAlign('vjustify');
    $format_metrics_daily->setVAlign('vcenter');
    $format_metrics_daily->setTextWrap();

    // Added headers since they never change in the Metics Group.
    $worksheet_metrics->write(0, 0, 'Week Range', $format_metrics_title);
    $worksheet_metrics->write(0, 1, 'Inbnd Field Emails', $format_metrics_title);
    $worksheet_metrics->write(0, 2, 'Inbnd Admin Emails', $format_metrics_title);
    $worksheet_metrics->write(0, 3, 'Weekly Total Inbnd', $format_metrics_title);
    $worksheet_metrics->write(0, 4, 'Outbnd Field Emails', $format_metrics_title);
    $worksheet_metrics->write(0, 5, 'Outbnd Admin Emails', $format_metrics_title);
    $worksheet_metrics->write(0, 6, 'Weekly Total Outbnd', $format_metrics_title);
    $worksheet_metrics->write(0, 7, 'Avg time to respond (hrs)', $format_metrics_title);
    $worksheet_metrics->write(0, 8, ' ', $format_metrics_title2);
    $worksheet_metrics->write(0, 9, ' ', $format_metrics_title2);
    $worksheet_metrics->write(0, 10, ' ', $format_metrics_title2);
    $worksheet_metrics->write(0, 11, ' ', $format_metrics_title2);
    $worksheet_metrics->write(0, 12, ' ', $format_metrics_title2);
    $worksheet_metrics->write(0, 13, ' ', $format_metrics_title2);
    $worksheet_metrics->write(0, 14, ' ', $format_metrics_title2);

    $week_range_text_metrics = date("n/j/y", $start_time) . " - " . date("n/j/y", $start_time+518400);
    $worksheet_metrics->write(1, 0, $week_range_text_metrics, $format_general);
    $worksheet_metrics->write(5, 0, 'Grand Total', $format_general);
    $worksheet_metrics->write(6, 0, 'Weekly Averages', $format_metrics_weekly);
    $worksheet_metrics->write(7, 0, 'Daily Averages', $format_metrics_daily);
    $worksheet_metrics->write(8, 0, '%', $format_general);

    // Setup templating for the formating of certain cells in the Inbound Count Group.
    $format_acd_in_title =& $workbook->addFormat();
    $format_acd_in_title->setSize(18);
    $format_acd_in_title->setColor(8);
    $format_acd_in_title->setFgColor(34);
    $format_acd_in_title->setBorder(1);
    $format_acd_in_title->setBold();
    $format_acd_in_title->setHAlign('center');
    $format_acd_in_title->setVAlign('vjustify');
    $format_acd_in_title->setVAlign('top');
    $format_acd_in_title->setTextWrap();
    $format_acd_in_title->setAlign('merge');

    $format_acd_in_title2 =& $workbook->addFormat();
    $format_acd_in_title2->setColor(8);
    $format_acd_in_title2->setFgColor(43);
    $format_acd_in_title2->setBorder(1);
    $format_acd_in_title2->setBold();
    $format_acd_in_title2->setHAlign('center');
    $format_acd_in_title2->setVAlign('vjustify');
    $format_acd_in_title2->setVAlign('top');
    $format_acd_in_title2->setTextWrap();
    $format_acd_in_title2->setAlign('merge');

    $format_acd_in_title3 =& $workbook->addFormat();
    $format_acd_in_title3->setColor(8);
    $format_acd_in_title3->setFgColor(47);
    $format_acd_in_title2->setBorder(1);
    $format_acd_in_title3->setBold();
    $format_acd_in_title3->setHAlign('center');
    $format_acd_in_title3->setVAlign('vjustify');
    $format_acd_in_title3->setVAlign('top');
    $format_acd_in_title3->setTextWrap();

    $format_acd_in_total =& $workbook->addFormat();
    $format_acd_in_title->setSize(15);
    $format_acd_in_total->setBorder(1);
    $format_acd_in_total->setColor(8);
    $format_acd_in_total->setFgColor(6);
    $format_acd_in_total->setBold();
    $format_acd_in_total->setHAlign('left');
    $format_acd_in_total->setVAlign('vjustify');
    $format_acd_in_total->setVAlign('top');

    // Added headers since they never change in the acd in Group.
    $worksheet_acd_in->write(0, 0, 'MetLife / '.$group_text, $format_acd_in_title);
    $worksheet_acd_in->write(0, 1, '', $format_acd_in_title);
    $worksheet_acd_in->write(0, 2, '', $format_acd_in_title);
    $worksheet_acd_in->write(0, 3, '', $format_acd_in_title);
    $worksheet_acd_in->write(0, 4, '', $format_acd_in_title);
    $worksheet_acd_in->write(0, 5, '', $format_acd_in_title);
    $worksheet_acd_in->write(0, 6, 'WEEKLY TOTALS', $format_acd_in_title);
    $worksheet_acd_in->write(0, 7, '', $format_acd_in_title);
    $worksheet_acd_in->write(0, 8, '', $format_acd_in_title);
    $worksheet_acd_in->write(0, 9, '', $format_acd_in_title);
    $worksheet_acd_in->write(1, 0, $week_range_text, $format_acd_in_title2);
    $worksheet_acd_in->write(1, 1, '', $format_acd_in_title2);
    $worksheet_acd_in->write(1, 2, '', $format_acd_in_title2);
    $worksheet_acd_in->write(1, 3, '', $format_acd_in_title2);
    $worksheet_acd_in->write(1, 4, '', $format_acd_in_title2);
    $worksheet_acd_in->write(1, 5, '', $format_acd_in_title2);
    $worksheet_acd_in->write(2, 0, 'Date', $format_acd_in_title3);
    $worksheet_acd_in->write(2, 1, 'Call Times', $format_acd_in_title3);
    $worksheet_acd_in->write(2, 2, 'Agent Talk', $format_acd_in_title3);
    $worksheet_acd_in->write(2, 3, '(MIN.SEC) Hold time', $format_acd_in_title3);
    $worksheet_acd_in->write(2, 4, '(MIN.SEC) Patch Time', $format_acd_in_title3);
    $worksheet_acd_in->write(2, 5, 'ANI', $format_acd_in_title3);
    $worksheet_acd_in->write(1, 6, 'Weekly Total Calls', $format_acd_in_total);
    $worksheet_acd_in->writeFormula(1, 7, "=count(A1:A1000)", $format_acd_in_title2);
    $worksheet_acd_in->write(1, 8, 'Weekly Average Patch Time', $format_acd_in_total);
    $worksheet_acd_in->writeFormula(1, 9, "=SUM(B2,E2)", $format_acd_in_title2);
    $worksheet_acd_in->write(2, 6, 'Weekly Agent Talk Time', $format_acd_in_total);
    $worksheet_acd_in->writeFormula(2, 7, "=SUM(B2,E2)", $format_acd_in_title2);
    $worksheet_acd_in->write(2, 8, 'Weekly Average Hold Time', $format_acd_in_total);
    $worksheet_acd_in->writeFormula(2, 9, "=SUM(B2,E2)", $format_acd_in_title2);

    // Setup templating for the formating of certain cells in the Inbound Count Group.
    $format_in_count_title =& $workbook->addFormat();
    $format_in_count_title->setSize(15);
    $format_in_count_title->setColor(8);
    $format_in_count_title->setBorder(1);
    $format_in_count_title->setFgColor(35);
    $format_in_count_title->setBold();
    $format_in_count_title->setHAlign('center');
    $format_in_count_title->setVAlign('vjustify');
    $format_in_count_title->setVAlign('top');
    $format_in_count_title->setTextWrap();

    $format_in_count_title2 =& $workbook->addFormat();
    $format_in_count_title2->setColor(8);
    $format_in_count_title2->setFgColor(47);
    $format_in_count_title2->setBold();
    $format_in_count_title2->setHAlign('center');
    $format_in_count_title2->setVAlign('vjustify');
    $format_in_count_title2->setVAlign('top');
    $format_in_count_title2->setTextWrap();

    $format_in_count_total =& $workbook->addFormat();
    $format_in_count_total->setBorder(1);
    $format_in_count_total->setColor(8);
    $format_in_count_total->setFgColor(6);
    $format_in_count_total->setBold();
    $format_in_count_total->setSize(18);
    $format_in_count_total->setHAlign('left');
    $format_in_count_total->setVAlign('vjustify');
    $format_in_count_total->setVAlign('top');

    $format_in_count_grand =& $workbook->addFormat();
    $format_in_count_grand->setBorder(1);
    $format_in_count_grand->setColor(8);
    $format_in_count_grand->setFgColor(43);
    $format_in_count_grand->setBold();
    $format_in_count_grand->setSize(18);
    $format_in_count_grand->setHAlign('left');
    $format_in_count_grand->setVAlign('vjustify');
    $format_in_count_grand->setVAlign('top');

    // Added headers since they never change in the Inbound Count Group.
    $worksheet_in_count->write(0, 0, 'Email Count Admin', $format_in_count_title);
    $worksheet_in_count->write(0, 1, 'Totals', $format_in_count_title);
    $worksheet_in_count->write(0, 2, 'Email Count Field', $format_in_count_title);
    $worksheet_in_count->write(0, 3, 'ID', $format_in_count_title);
    $worksheet_in_count->write(0, 4, 'Totals', $format_in_count_title);

    // Setup templating for the formating of certain cells in the Outbound Count Group.
    $format_out_count_title =& $workbook->addFormat();
    $format_out_count_title->setSize(15);
    $format_out_count_title->setColor(8);
    $format_out_count_title->setBorder(1);
    $format_out_count_title->setFgColor(14);
    $format_out_count_title->setBold();
    $format_out_count_title->setHAlign('center');
    $format_out_count_title->setVAlign('vjustify');
    $format_out_count_title->setVAlign('top');
    $format_out_count_title->setTextWrap();

    $format_out_count_title2 =& $workbook->addFormat();
    $format_out_count_title2->setColor(8);
    $format_out_count_title2->setFgColor(47);
    $format_out_count_title2->setBold();
    $format_out_count_title2->setHAlign('center');
    $format_out_count_title2->setVAlign('vjustify');
    $format_out_count_title2->setVAlign('top');
    $format_out_count_title2->setTextWrap();

    $format_out_count_total =& $workbook->addFormat();
    $format_out_count_total->setBorder(1);
    $format_out_count_total->setColor(8);
    $format_out_count_total->setFgColor(42);
    $format_out_count_total->setBold();
    $format_out_count_total->setSize(18);
    $format_out_count_total->setHAlign('left');
    $format_out_count_total->setVAlign('vjustify');
    $format_out_count_total->setVAlign('top');

    $format_out_count_grand =& $workbook->addFormat();
    $format_out_count_grand->setBorder(1);
    $format_out_count_grand->setColor(8);
    $format_out_count_grand->setFgColor(43);
    $format_out_count_grand->setBold();
    $format_out_count_grand->setSize(18);
    $format_out_count_grand->setHAlign('left');
    $format_out_count_grand->setVAlign('vjustify');
    $format_out_count_grand->setVAlign('top');

    // Added headers since they never change in the Outbound Count Group.
    $worksheet_out_count->write(0, 0, 'Email Count Admin', $format_out_count_title);
    $worksheet_out_count->write(0, 1, 'Totals', $format_out_count_title);
    $worksheet_out_count->write(0, 2, 'Email Count Field', $format_out_count_title);
    $worksheet_out_count->write(0, 3, 'ID', $format_out_count_title);
    $worksheet_out_count->write(0, 4, 'Totals', $format_out_count_title);

    // Setup templating for the formating of certain cells in the Inbound Group.
    $format_inbound_title =& $workbook->addFormat();
    $format_inbound_title->setSize(15);
    $format_inbound_title->setColor(8);
    $format_inbound_title->setBorder(1);
    $format_inbound_title->setFgColor(35);
    $format_inbound_title->setBold();
    $format_inbound_title->setHAlign('center');
    $format_inbound_title->setVAlign('vjustify');
    $format_inbound_title->setVAlign('top');
    $format_inbound_title->setTextWrap();

    $format_inbound_title2 =& $workbook->addFormat();
    $format_inbound_title2->setColor(8);
    $format_inbound_title2->setFgColor(47);
    $format_inbound_title2->setBold();
    $format_inbound_title2->setHAlign('center');
    $format_inbound_title2->setVAlign('vjustify');
    $format_inbound_title2->setVAlign('top');
    $format_inbound_title2->setTextWrap();

    // Setup templating for the formating of certain cells in the Inbound Group.
    $format_inbound_title3 =& $workbook->addFormat();
    $format_inbound_title3->setSize(15);
    $format_inbound_title3->setColor(8);
    $format_inbound_title3->setBorder(1);
    $format_inbound_title3->setFgColor(34);
    $format_inbound_title3->setBold();
    $format_inbound_title3->setHAlign('center');
    $format_inbound_title3->setVAlign('vjustify');
    $format_inbound_title3->setVAlign('top');
    $format_inbound_title3->setTextWrap();

    // Added headers since they never change in the Inbound Group.
    $worksheet_inbound->setInputEncoding('utf-8');
    $worksheet_inbound->write(0, 0, 'Inbound Email From', $format_inbound_title);
    $worksheet_inbound->write(0, 1, 'User Name', $format_inbound_title);
    $worksheet_inbound->write(0, 2, 'ID', $format_inbound_title);
    $worksheet_inbound->write(0, 3, 'Date Email Received', $format_inbound_title);
    $worksheet_inbound->write(0, 4, 'Ticket Mask', $format_inbound_title);
    $worksheet_inbound->write(0, 5, 'Subject Line', $format_inbound_title);
    $worksheet_inbound->write(0, 6, 'Email Contents', $format_inbound_title);
    $worksheet_inbound->write(0, 7, 'Category', $format_inbound_title3);
    $worksheet_inbound->write(0, 8, 'Code', $format_inbound_title3);
    $worksheet_inbound->write(0, 9, 'Description(or snapshot)', $format_inbound_title3);
    $worksheet_inbound->write(0, 10, 'Group', $format_inbound_title);
    $worksheet_inbound->write(1, 0, $week_range_text, $format_inbound_title2);
    $worksheet_inbound->write(1, 1, "", $format_inbound_title2);
    $worksheet_inbound->write(1, 2, "", $format_inbound_title2);
    $worksheet_inbound->write(1, 3, "", $format_inbound_title2);
    $worksheet_inbound->write(1, 4, "", $format_inbound_title2);
    $worksheet_inbound->write(1, 5, "", $format_inbound_title2);
    $worksheet_inbound->write(1, 6, "", $format_inbound_title2);
    $worksheet_inbound->write(1, 7, "", $format_inbound_title2);
    $worksheet_inbound->write(1, 8, "", $format_inbound_title2);
    $worksheet_inbound->write(1, 9, "", $format_inbound_title2);
    $worksheet_inbound->write(1, 10, "", $format_inbound_title2);

    // Setup templating for the formating of certain cells in the Outbound Group.
    $format_outbound_title =& $workbook->addFormat();
    $format_outbound_title->setSize(15);
    $format_outbound_title->setColor(8);
    $format_outbound_title->setBorder(1);
    $format_outbound_title->setFgColor(11);
    $format_outbound_title->setBold();
    $format_outbound_title->setHAlign('center');
    $format_outbound_title->setVAlign('vjustify');
    $format_outbound_title->setVAlign('top');
    $format_outbound_title->setTextWrap();

    $format_outbound_title2 =& $workbook->addFormat();
    $format_outbound_title2->setColor(8);
    $format_outbound_title2->setFgColor(6);
    $format_outbound_title2->setBold();
    $format_outbound_title2->setHAlign('center');
    $format_outbound_title2->setVAlign('vjustify');
    $format_outbound_title2->setVAlign('top');
    $format_outbound_title2->setTextWrap();

    $format_outbound_title3 =& $workbook->addFormat();
    $format_outbound_title3->setSize(15);
    $format_outbound_title3->setColor(8);
    $format_outbound_title3->setBorder(1);
    $format_outbound_title3->setFgColor(34);
    $format_outbound_title3->setBold();
    $format_outbound_title3->setHAlign('center');
    $format_outbound_title3->setVAlign('vjustify');
    $format_outbound_title3->setVAlign('top');
    $format_outbound_title3->setTextWrap();

    // Added headers since they never change in the Outbound Group.
    $worksheet_outbound->setInputEncoding('utf-8');
    $worksheet_outbound->write(0, 0, 'Outbound Email To', $format_outbound_title);
    $worksheet_outbound->write(0, 1, 'User Name', $format_outbound_title);
    $worksheet_outbound->write(0, 2, 'ID', $format_outbound_title);
    $worksheet_outbound->write(0, 3, 'Date Email Sent', $format_outbound_title);
    $worksheet_outbound->write(0, 4, 'Ticket Mask', $format_outbound_title);
    $worksheet_outbound->write(0, 5, 'Subject Line', $format_outbound_title);
    $worksheet_outbound->write(0, 6, 'Email Contents', $format_outbound_title);
    $worksheet_outbound->write(0, 7, 'Category', $format_outbound_title3);
    $worksheet_outbound->write(0, 8, 'Code', $format_outbound_title3);
    $worksheet_outbound->write(0, 9, 'Description(or snapshot)', $format_outbound_title3);
    $worksheet_outbound->write(0, 10, 'Responder', $format_outbound_title);
    $worksheet_outbound->write(0, 11, 'Group', $format_outbound_title);

    $worksheet_outbound->write(1, 0, $week_range_text, $format_outbound_title2);
    $worksheet_outbound->write(1, 1, "", $format_outbound_title2);
    $worksheet_outbound->write(1, 2, "", $format_outbound_title2);
    $worksheet_outbound->write(1, 3, "", $format_outbound_title2);
    $worksheet_outbound->write(1, 4, "", $format_outbound_title2);
    $worksheet_outbound->write(1, 5, "", $format_outbound_title2);
    $worksheet_outbound->write(1, 6, "", $format_outbound_title2);
    $worksheet_outbound->write(1, 7, "", $format_outbound_title2);
    $worksheet_outbound->write(1, 8, "", $format_outbound_title2);
    $worksheet_outbound->write(1, 9, "", $format_outbound_title2);
    $worksheet_outbound->write(1, 10, "", $format_outbound_title2);
    $worksheet_outbound->write(1, 11, "", $format_outbound_title2);

    print $translate->_('reports.ui.group.emailresponse.answernet.done');
    print '<br>';
    print $translate->_('reports.ui.group.emailresponse.answernet.metlife.generating.email.detail');

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

    print $translate->_('reports.ui.group.emailresponse.answernet.done');
    print '<br>';
    print $translate->_('reports.ui.group.emailresponse.answernet.metlife.generating.email.count');

    $worksheet_in_count->setRow(1, 24);
    $row_count = 2;
    foreach ($in_count_admin as $record) {
      $worksheet_in_count->write($row_count, 0, $record['email'], $format_general);
      $worksheet_in_count->write($row_count, 1, $record['count'], $format_general);
      $row_count++;
    }
    $worksheet_in_count->write(1, 0, 'Total Admin Email', $format_in_count_total);
    $worksheet_in_count->writeFormula(1, 1, "=SUM(B3:B".$row_count.")", $format_in_count_total);
    $row_count = 2;
    foreach ($in_count_other as $record) {
      $worksheet_in_count->write($row_count, 2, $record['email'], $format_general);
      $worksheet_in_count->write($row_count, 3, '', $format_general);
      $worksheet_in_count->write($row_count, 4, $record['count'], $format_general);
      $row_count++;
    }
    $worksheet_in_count->write(1, 2, 'Total Field Email', $format_in_count_total);
    $worksheet_in_count->write(1, 3, '', $format_in_count_total);
    $worksheet_in_count->writeFormula(1, 4, "=SUM(E3:E".$row_count.")", $format_in_count_total);
    // Grand Total
    $worksheet_in_count->write(1, 5, 'Grand Total Email', $format_in_count_grand);
    $worksheet_in_count->writeFormula(1, 6, "=SUM(B2,E2)", $format_in_count_grand);

    $worksheet_out_count->setRow(1, 24);
    $row_count = 2;
    foreach ($out_count_admin as $record) {
      $worksheet_out_count->write($row_count, 0, $record['email'], $format_general);
      $worksheet_out_count->write($row_count, 1, $record['count'], $format_general);
      $row_count++;
    }
    $worksheet_out_count->write(1, 0, 'Total Admin Email', $format_out_count_total);
    $worksheet_out_count->writeFormula(1, 1, "=SUM(B3:B".$row_count.")", $format_out_count_total);
    $row_count = 2;
    foreach ($out_count_other as $record) {
      $worksheet_out_count->write($row_count, 2, $record['email'], $format_general);
      $worksheet_out_count->write($row_count, 3, '', $format_general);
      $worksheet_out_count->write($row_count, 4, $record['count'], $format_general);
      $row_count++;
    }
    $worksheet_out_count->write(1, 2, 'Total Field Email', $format_out_count_total);
    $worksheet_out_count->write(1, 3, '', $format_out_count_total);
    $worksheet_out_count->writeFormula(1, 4, "=SUM(E3:E".$row_count.")", $format_out_count_total);
    // Grand Total
    $worksheet_out_count->write(1, 5, 'Grand Total Email', $format_out_count_grand);
    $worksheet_out_count->writeFormula(1, 6, "=SUM(B2,E2)", $format_out_count_grand);

    $workbook->close();
    print $translate->_('reports.ui.group.emailresponse.answernet.done');
    print '<br>';
    print $translate->_('reports.ui.group.emailresponse.answernet.metlife.generating');
		print $translate->_('reports.ui.group.emailresponse.answernet.done');
		print '<br><br>';
		print '<b><a href=' . $href_filename . '>' . $translate->_('reports.ui.group.emailresponse.answernet.download.xls') . '</a></b>';
		print '<br><br>';
	}

};
endif;

?>
