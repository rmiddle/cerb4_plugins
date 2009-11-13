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
    // 4 = Request Type /0/2/4/6/8/10/12/14
    // 5 = SLA
    // 6 = New Hire Yes = 0 / No = 2
    // 
    $sub = explode(',', $subject, 2);
    $lname = $sub[0];
    $sub2 = explode("<", $sub[1]);
    $fname = $sub2[0];
    $emp_id = $sub2[1];
    $type_custom = $sub2[2];
    $message->custom_fields['2'] = substr($emp_id, 0, -1);
    $message->custom_fields['3'] = trim($fname) . " " . trim($lname);
   
//    $message->body .= "type = " . substr($type_custom, 0, -1);
	}

	// function renderConfig(Model_PreParseRule $filter=null) {}

	// function saveConfig() {}

};
