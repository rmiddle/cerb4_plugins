<?php
$db = DevblocksPlatform::getDatabaseService();
$datadict = NewDataDictionary($db); /* @var $datadict ADODB_DataDict */ // ,'mysql'

$tables = $datadict->MetaTables();
$tables = array_flip($tables);

return TRUE;
