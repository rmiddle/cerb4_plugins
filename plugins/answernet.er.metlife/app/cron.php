<?php

class AnswernetMetlifeCron extends CerberusCronPageExtension {
  const EXTENSION_ID = 'answernet.er.metlife.id.cron';

  function run() {
    $logger = DevblocksPlatform::getConsoleLog();
    $logger->info("[Answernet.com] Running Metlife DR report and emailing it.");
 
    @ini_set('memory_limit','64M');
 
    $filename = AnswernetMetlifeReportGroupReportDR::AnswernetMetlifeReportDRReport(1);
    $full_filename = getcwd().'/storage/answernet/'.$filename;
    
    $logger->info("[Answernet.com] filename = ".$filename);
    $logger->info("[Answernet.com] full_filename = ".$full_filename);

    @$answernet_email01 = $this->getParam('answernet_email01', NULL);    
    @$answernet_email02 = $this->getParam('answernet_email02', NULL);    
    @$answernet_email03 = $this->getParam('answernet_email03', NULL);    
    @$answernet_email04 = $this->getParam('answernet_email04', NULL);    
    @$answernet_email05 = $this->getParam('answernet_email05', NULL);    

    if ($answernet_email01 == '') {
      $logger->info("[Answernet.com] answernet_email01 = NULL");
    } else {
      $logger->info("[Answernet.com] answernet_email01 = ".$answernet_email01);
    }
    if ($answernet_email02 == '') {
      $logger->info("[Answernet.com] answernet_email02 = NULL");
    } else {
      $logger->info("[Answernet.com] answernet_email02 = ".$answernet_email02);
    }
    if ($answernet_email03 == '') {
      $logger->info("[Answernet.com] answernet_email03 = NULL");
    } else {
      $logger->info("[Answernet.com] answernet_email03 = ".$answernet_email03);
    }
    if ($answernet_email04 == '') {
      $logger->info("[Answernet.com] answernet_email04 = NULL");
    } else {
      $logger->info("[Answernet.com] answernet_email04 = ".$answernet_email04);
    }
    if ($answernet_email05 == '') {
      $logger->info("[Answernet.com] answernet_email05 = NULL");
    } else {
      $logger->info("[Answernet.com] answernet_email05 = ".$answernet_email05);
    }
 
    $logger->info("[Answernet.com] Finished processing Metlife DR report.");
  }
 
  function configure($instance) {
    $tpl = DevblocksPlatform::getTemplateService();
    $tpl->cache_lifetime = "0";
    $tpl_path = dirname(dirname(__FILE__)) . '/templates/';
    $tpl->assign('path', $tpl_path);

    $tpl->assign('answernet_email01', $this->getParam('answernet_email01', ''));
    $tpl->assign('answernet_email02', $this->getParam('answernet_email02', ''));
    $tpl->assign('answernet_email03', $this->getParam('answernet_email03', ''));
    $tpl->assign('answernet_email04', $this->getParam('answernet_email04', ''));
    $tpl->assign('answernet_email05', $this->getParam('answernet_email05', ''));

    $tpl->display($tpl_path . 'cron.tpl');
  }
 
  function saveConfigurationAction() {
    @$answernet_email01 = DevblocksPlatform::importGPC($_POST['answernet_email01'],'string','');
    @$answernet_email02 = DevblocksPlatform::importGPC($_POST['answernet_email02'],'string','');
    @$answernet_email03 = DevblocksPlatform::importGPC($_POST['answernet_email03'],'string','');
    @$answernet_email04 = DevblocksPlatform::importGPC($_POST['answernet_email04'],'string','');
    @$answernet_email05 = DevblocksPlatform::importGPC($_POST['answernet_email05'],'string','');
    $this->setParam('answernet_email01', $answernet_email01);
    $this->setParam('answernet_email02', $answernet_email02);
    $this->setParam('answernet_email03', $answernet_email03);
    $this->setParam('answernet_email04', $answernet_email04);
    $this->setParam('answernet_email05', $answernet_email05);
  }
};