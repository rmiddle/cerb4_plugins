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
//    $logger->info(print_r($message_headers->subject));
//    $logger->info(print_r($filter));
//    $logger->info(print_r($ticket_fields));

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

    // If topic == Other
    if (preg_match('/Other/i', $topic_metlife)) {
      $message->custom_fields['4'] = "Other";
      $message->custom_fields['5'] = 5;
      $message->custom_fields['6'] = "No";
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

// $date_var = strtotime("Last Monday");
// for ($i = 1; $i <= 10; $i++) {
//   $week_choices[$i]['time'] = $date_var;
//   $week_choices[$i]['text'] = "Week of ".date("W: n/j/y", $date_var).'
//- '.date("n/j/y", $date_var+518400);
//      $date_var -= 604800;
//    }

	// function renderConfig(Model_PreParseRule $filter=null) {}

	// function saveConfig() {}

};
