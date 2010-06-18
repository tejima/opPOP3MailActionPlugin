<?php
class POP3MailActionPluginConfigForm extends sfForm
{
  protected $configs = array(
    'pop3_host' => 'oppop3_pop3_host',
    'pop3_user' => 'oppop3_pop3_user',
    'pop3_pass' => 'oppop3_pop3_pass',
  );
  public function configure()
  {
    $this->setWidgets(array(
    'pop3_host' => new sfWidgetFormInput(),
    'pop3_user' => new sfWidgetFormInput(),
    'pop3_pass' => new sfWidgetFormInput(),
    ));

    $this->setValidators(array(
    'pop3_host' => new sfValidatorString(array(),array()),
    'pop3_user' => new sfValidatorString(array(),array()),
    'pop3_pass' => new sfValidatorString(array(),array()),
    ));

    $this->widgetSchema->setHelp('pop3_host','POP3ホスト名');
    $this->widgetSchema->setHelp('pop3_user','POP3ユーザー名');
    $this->widgetSchema->setHelp('pop3_pass','POP3パスワード');

    foreach($this->configs as $k => $v)
    {
      $config = Doctrine::getTable('SnsConfig')->retrieveByName($v);
  
      if($config)
      {   
        $this->getWidgetSchema()->setDefault($k,$config->getValue());
      }
    }
    $this->getWidgetSchema()->setNameFormat('pop3[%s]');
  }
  public function save(){
    foreach($this->getValues() as $k => $v)
    {
      if(!isset($this->configs[$k]))
      {
        continue;
      }
      $config = Doctrine::getTable('SnsConfig')->retrieveByName($this->configs[$k]);
      if(!$config)
      {
        $config = new SnsConfig();
        $config->setName($this->configs[$k]);
      }
      $config->setValue($v);
      $config->save();
    }
  }
  public function validate($validator,$value,$arguments = array())
  {
    return $value; 
  }
}
?>
