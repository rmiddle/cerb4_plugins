<?php

$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql' 

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

// ===========================================================================
// Add a table for new watcher 2.0 filters

if(!isset($tables['cerb5blog_watchers2_filter'])) {
	$flds ="
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		pos I2 DEFAULT 0 NOTNULL,
		name C(128) DEFAULT '' NOTNULL,
		created I4 DEFAULT 0 NOTNULL,
		worker_id I4 DEFAULT 0 NOTNULL,
		criteria_ser XL,
		actions_ser XL,
		is_disabled I1 DEFAULT 0 NOTNULL
	";
	$sql = $datadict->CreateTableSQL('cerb5blog_watchers2_filter', $flds);
	$datadict->ExecuteSQLArray($sql);
}

// `mail_template` =======================
if(!isset($tables['cerb5blog_watchers2_template'])) {
	$flds = "
		id I4 DEFAULT 0 NOTNULL PRIMARY,
		title C(64) DEFAULT '' NOTNULL,
		description C(255) DEFAULT '' NOTNULL,
		template_type I1 DEFAULT 0 NOTNULL,
		template_subject C(255) DEFAULT '' NOTNULL,
		template_content XL,
		owner_id I4 DEFAULT 0 NOTNULL
	";
	$sql = $datadict->CreateTableSQL('cerb5blog_watchers2_template',$flds);
	$datadict->ExecuteSQLArray($sql);
}

return TRUE;
