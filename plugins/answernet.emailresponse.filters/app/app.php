<?php

class AnswernetMetlifeFilterPlugin extends DevblocksPlugin {};

class AnswernetMetlifeFilterCopyAction extends Extension_MailFilterAction {
	const EXTENSION_ID = 'answernet.metlife_filters.action.copy';

	function __construct($manifest) {
		$this->DevblocksExtension($manifest,1);
	}

	function run(Model_PreParseRule $filter, CerberusParserMessage $message) {
    $message_headers = $message->headers;
		$ticket_fields = DAO_CustomField::getAll();
		$params = $filter->actions[self::EXTENSION_ID];

    $logger = DevblocksPlatform::getConsoleLog();
    $logger->info("Answernet: Running Filter on New Mail");
    $logger->info(print_r($message_headers->subject));
    echo "Answernet: Running Filter on New Mail<br>";
    print_r($message_headers->subject);
    echo "<br>";
//    $logger->info(print_r($filter));
//    $logger->info(print_r($ticket_fields));

    // Houser,Colin <1034179><Missing Serviced Customer information>

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
