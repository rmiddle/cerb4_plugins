<?php

class AnswernetMetlifeFilterPlugin extends DevblocksPlugin {};

class AnswernetMetlifeFilterCopyAction extends Extension_MailFilterAction {
	const EXTENSION_ID = 'answernet.metlife_filters.action.copy';

	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}

	function run(Model_PreParseRule $filter, CerberusParserMessage $message) {
    $message_headers = $message->headers;
    $subject = DevblocksPlatform::parseCrlfString($message->headers['subject']);
		$ticket_fields = DAO_CustomField::getAll();
		$params = $filter->actions[self::EXTENSION_ID];

    $logger = DevblocksPlatform::getConsoleLog();
    $logger->info("Answernet: Running Filter on New Mail");
//    $logger->info(print_r($message_headers->subject));
//    $logger->info(print_r($filter));
//    $logger->info(print_r($ticket_fields));

    // Houser,Colin <1034179><Missing Serviced Customer information>
    // Current custom_fields numbers
    // 1 = RM Name
    // 2 = New Hire Yes = 0 / No = 2
    // 3 = RM Employee ID
    // 6 = Request Type /0/2/4/6/8/10/12/14
    // 8 = Due Date
    // 9 = SLA
    // 
    print_r($subject)
    $sub = explode(',', $subject, 2);
    print_r($sub);
    $lname = $sub[0];
    print_r($lname);
    $sub2 = explode("<", $sub[1]);
    print_r($sub2)
    $fname = $sub2[0];
    print_r($fname);
    $emp_id = $sub2[1];
    print_r($emp_id);
    $type_custom = $sub2[2];
    print_r($type_custom);
//    $message->custom_fields['1'] = trim($fname) . trim($lname);
//    $message->custom_fields['3'] = (int)$emp_id);
//    $message->body .= "emp_id = " . $emp_id;
//    $message->body .= "type = " . $type_custom;
//    $message->body .= "fname = " . $fname;
//    $message->body .= "lname = " . $lname;
//    $message->body .= "sub = " . $sub;
//    $message->body .= "sub2 = " . $sub2;

				// collapse multi-line headers to single line for single-line text fields
				//if($ticket_fields[$custom_fields[$idx]]->type == Model_CustomField::TYPE_SINGLE_LINE) {
				//	$message->custom_fields[$custom_fields[$idx]]
				//		= trim(implode(' ',$value));
				//} elseif($ticket_fields[$custom_fields[$idx]]->type == Model_CustomField::TYPE_MULTI_LINE) {
				//	$message->custom_fields[$custom_fields[$idx]]
				//		= trim(implode('\r\n',$value));
	}

	// function renderConfig(Model_PreParseRule $filter=null) {}

	// function saveConfig() {}

};
