<?php

class AnswernetMetlifePlugin extends DevblocksPlugin {
  function load(DevblocksPluginManifest $manifest) {
  }
};

if (class_exists('DevblocksTranslationsExtension',true)):
  class AnswernetMetlifeTranslations extends DevblocksTranslationsExtension {
    function __construct($manifest) {
      parent::__construct($manifest);
    }

    function getTmxFile() {
      return dirname(dirname(__FILE__)) . '/strings.xml';
    }
  };
endif;

class AnswernetMetlifeReportGroup extends Extension_ReportGroup {
  function __construct($manifest) {
    parent::__construct($manifest);
  }
};

