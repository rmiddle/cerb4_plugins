<?php

class AnswernetMetlifeFilterPlugin extends DevblocksPlugin {};

class AnswernetMetlifeFilterCopyAction extends Extension_MailFilterAction {
	const EXTENSION_ID = 'answernet.metlife_filters.action.copy';

	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}

	function run(Model_PreParseRule $filter, CerberusParserMessage $message) {
    $message_headers = $message->headers;
    $subject = $message->headers['subject'];
		$ticket_fields = DAO_CustomField::getAll();
		$params = $filter->actions[self::EXTENSION_ID];
    $day_of_week = date('N');

    $logger = DevblocksPlatform::getConsoleLog();
    $logger->info("Answernet: Running Filter on New Mail");

    // Houser,Colin <1034179><Missing Serviced Customer information>
    // Current custom_fields numbers
    // 1 = Due Date
    // 2 = RM Employee ID
    // 3 = RM Name
    // 4 = Topic_metlife /0/2/4/6/8/10/12/14
    // 5 = SLA
    // 6 = New Hire Yes = 0 / No = 2
    // 
    $sub = explode(',', $subject, 2);
    $lname = $sub[0];
    $sub2 = explode("<", $sub[1]);
    $fname = $sub2[0];
    $emp_id = $sub2[1];
    $topic_metlife = $sub2[2];
    $message->custom_fields['2'] = substr($emp_id, 0, -1);
    $message->custom_fields['3'] = trim($fname) . " " . trim($lname);

    // If topic == Import contacts
    if (preg_match('/import/i', $topic_metlife)) {
      $message->custom_fields['4'] = "Import contacts";
      $message->custom_fields['5'] = 1;
      $message->custom_fields['6'] = "Yes";
    }

    // If topic == Create mailing list from existing data
    if (preg_match('/mailing/i', $topic_metlife)) {
      $message->custom_fields['4'] = "Create mailing list from existing data";
      $message->custom_fields['5'] = 3;
      $message->custom_fields['6'] = "Yes";
    }

    // If topic == Update existing contacts
    if (preg_match('/Update/i', $topic_metlife)) {
      $message->custom_fields['4'] = "Update existing contacts";
      $message->custom_fields['5'] = 3;
      $message->custom_fields['6'] = "No";
    }

    // If topic == Research missing customer info
    if (preg_match('/Research/i', $topic_metlife)) {
      $message->custom_fields['4'] = "Research missing customer info";
      $message->custom_fields['5'] = 5;
      $message->custom_fields['6'] = "No";
    }

    // If topic == Create labels
    if (preg_match('/labels/i', $topic_metlife)) {
      $message->custom_fields['4'] = "Create labels";
      $message->custom_fields['5'] = 1;
      $message->custom_fields['6'] = "No";
    }

    // If topic == Export third-party file
    if (preg_match('/third/i', $topic_metlife)) {
      $message->custom_fields['4'] = "Export third-party file";
      $message->custom_fields['5'] = 3;
      $message->custom_fields['6'] = "No";
    }

    // If topic == Other
    if (preg_match('/Other/i', $topic_metlife)) {
      $message->custom_fields['4'] = "Other";
      $message->custom_fields['5'] = 5;
      $message->custom_fields['6'] = "No";
    }

    // SLA of 1.  Process day of week Busness Days suck.
    if ($message->custom_fields['5'] == 1) {
      if (($day_of_week < 5) || ($day_of_week == 7)) {
        $message->custom_fields['1'] = strtotime("+1 Days");
      }
      if ($day_of_week == 6) {
        $message->custom_fields['1'] = strtotime("+2 Days");
      }
      if ($day_of_week == 5) {
        $message->custom_fields['1'] = strtotime("+3 Days");
      }
    }

    // SLA of 3.  Process day of week Busness Days suck.
    if ($message->custom_fields['5'] == 3) {
      if (($day_of_week < 4) || ($day_of_week == 7)) {
        $message->custom_fields['1'] = strtotime("+3 Days");
      }
      if ($day_of_week == 6) {
        $message->custom_fields['1'] = strtotime("+4 Days");
      }
      if (($day_of_week > 3) && ($day_of_week < 6)) {
        $message->custom_fields['1'] = strtotime("+5 Days");
      }
    }

    // SLA of 5.  Process day of week Busness Days suck.
    if ($message->custom_fields['5'] == 5) {
      if (($day_of_week == 1) || ($day_of_week == 7)) {
        $message->custom_fields['1'] = strtotime("+5 Days");
      }
      if ($day_of_week == 6) {
        $message->custom_fields['1'] = strtotime("+6 Days");
      }
      if (($day_of_week > 1) && ($day_of_week < 6)) {
        $message->custom_fields['1'] = strtotime("+7 Days");
      }
    }
	}

	// function renderConfig(Model_PreParseRule $filter=null) {}

	// function saveConfig() {}

};
