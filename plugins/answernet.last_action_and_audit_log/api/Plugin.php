<?php
class AnswernetLastActionAndAuditLogPlugin extends DevblocksPlugin {
  function load(DevblocksPluginManifest $manifest) {
  }
};

if (class_exists('DevblocksTranslationsExtension',true)):
	class AnswernetLastActionAndAuditLogTranslations extends DevblocksTranslationsExtension {
		function __construct($manifest) {
			parent::__construct($manifest);
		}

		function getTmxFile() {
			return dirname(dirname(__FILE__)) . '/strings.xml';
		}
	};
endif;
