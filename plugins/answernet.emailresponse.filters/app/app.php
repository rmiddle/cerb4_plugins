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
    $logger->info("Answernet: Running Filter on New Metlife Mail");

    // Houser,Colin <1034179><Missing Serviced Customer information>
    // Current custom_fields numbers
    // 1 = RM Name
    // 2 = New Hire Yes = 0 / No = 2
    // 3 = RM Employee ID
    // 6 = Request Type /0/2/4/6/8/10/12/14
    //    0 = Import contacts
    //    2 = Create mailing list from existing data
    //    4 = Update existing contacts
    //    6 = Research missing customer info
    //    8 = Create labels
    //   10 = Fix duplicate contacts
    //   12 = Export third-party file
    //   14 = Other
    // 8 = Due Date
    // 9 = SLA
    // 
    $sub = explode(',', $subject, 2);
    $lname = $sub[0];
    $sub2 = explode("<", $sub[1]);
    $fname = $sub2[0];
    $emp_id = $sub2[1];
    $type_custom = $sub2[2];
    $message->custom_fields['1'] = trim($fname) . " " . trim($lname);
    $message->custom_fields['3'] = substr($emp_id, 0, -1);

    $message->body .= "type = " . substr($type_custom, 0, -1);

    // SLA
    // 1 = Import contacts NEW HIRE in last 45 days
    // 1 = Create Labels
    // 3 = Import Contacts
    // 3 = Create Mailing list from existing data
    // 3 = Update existing contacts
    // 3 = Fix duplicate contacts
    // 3 = Export third-party file
    // 5 = Missing or incorrect customer info
    // 5 = Other

	}

	// function renderConfig(Model_PreParseRule $filter=null) {}

	// function saveConfig() {}

};
