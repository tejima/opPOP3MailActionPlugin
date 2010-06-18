<?php 
class POP3Task extends sfBaseTask
{
  protected function configure()
  {
    set_time_limit(120);
    mb_language("Japanese");
    mb_internal_encoding("utf-8");

    $this->namespace = 'tjm';
    $this->name      = 'POP3';
    $this->aliases   = array('tjm-pop3');
    $this->breafDescription = '';
  }
  protected function execute($arguments = array(),$options = array())
  {
    $databaseManager = new sfDatabaseManager($this->configuration);
    $this->test_fetchpop3();
  }
  private function test_fetchpop3(){

    sfConfig::set('sf_test', true); //これを置かないと止まる ProjectConfigurationで。
    echo "---------------------------->processPOP3() @pne.jp \n";
    try{
      $mail = new Zend_Mail_Storage_Pop3(
         array('host' => Doctrine::getTable('SnsConfig')->get('oppop3_pop3_host'),
              'user' => Doctrine::getTable('SnsConfig')->get('oppop3_pop3_user'),
              'password' => Doctrine::getTable('SnsConfig')->get('oppop3_pop3_pass'),
              'ssl' => 'SSL',
              'port' => 995)
         );
      echo $mail->countMessages() . " messages found(from POP3 Server)\n";
      $count = $mail->countMessages();
      if($count == 0){
        return;
      }
      mb_internal_encoding('UTF-8');
      $raw_data = $mail->getRawHeader(1) . "\r\n\r\n" .  $mail->getRawContent(1);
      //$opMessage = new opMailMessage(array('raw' =>$raw_data));
      echo "--------------------------opMessage.content\n";

      $message = new opMailMessage(array('raw' => $raw_data));
      opMailRequest::setMailMessage($message);

      opApplicationConfiguration::unregisterZend();

      $configuration = ProjectConfiguration::getApplicationConfiguration('pc_frontend', 'prod', false);
      $context = sfContext::createInstance($configuration);
      $request = $context->getRequest();

      ob_start();
      $context->getController()->dispatch();
      $retval = ob_get_clean();

      if ($retval)
      {
        $subject = $context->getResponse()->getTitle();
        $to = $message->from;
        $from = $message->to;
        opMailSend::execute($subject, $to, $from, $retval);
      }
      $mail->removeMessage(1);
    }catch(Exception $e){
       echo $e->getMessage();
    }
  }
}
