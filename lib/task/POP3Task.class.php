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
    $app = sfYaml::load(sfConfig::get('sf_root_dir').'/plugins/twipnePlugin/config/app.yml');
    echo "---------------------------->processPOP3() @pne.jp \n";
    try{
      $mail = new Zend_Mail_Storage_Pop3(
         array('host' => 'pop.gmail.com' ,
              'user' => '20100608test@pne.jp',
              'password' => 'xxxxxxxx',
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
        $opMessage = new opMailMessage(array('raw' =>$raw_data));
        echo "--------------------------opMessage.content\n";
        print_r(bin2hex($opMessage->getContent()));

        //$this->queue->processQueing($member,$opMessage->getContent());
        $mail->removeMessage(1);
    }catch(Exception $e){
       echo $e->getMessage();
    }
  }
  private function shuffle(){
    $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $target_event_id = Doctrine::getTable('SnsConfig')->get('target_lunch_event');

    if(!$target_event_id){
      echo "no target event";
      return false;
    }

    $eMembersList = Doctrine::getTable('CommunityEventMember')->findByCommunityEventId($target_event_id);
    $members = array();
    foreach($eMembersList as $member){
      $m = Doctrine::getTable('Member')->find($member->member_id);
      $members[$member->member_id]['nickname'] = $m->name;
    }

    //グループ数の目安
    $group_len = floor((count($members) - 2) / 4);
    
    //対象イベントのコメント。参加希望を取得する
    $commentList = Doctrine::getTable('CommunityEventComment')->findByCommunityEventId($target_event_id);
    foreach($commentList as $comment){
      $body = $comment->getBody();
      $body = strtoupper(mb_convert_kana($body,'a','UTF8'));
      $body = preg_replace('/で|希望/','陣',$body);
      $body = preg_replace('/～|~|から|以降/','-',$body);
      $body = preg_replace('/([A-E])(まで|より前|以前)/','-$1',$body);
      if (preg_match('/([A-E])--?([A-E]?)/',$body,$match))
      {
        $min = strpos($alphabet,$match[1]);
        $max = 3;
        if ($match[2])
        {
          $max = strpos($alphabet,$match[2]);
        }
        if ($group_len < 3)
        {
          $max = $group_len;
        }
        $r = rand($min,$max);
        $body = preg_replace('/[A-E]--?[A-E]?/',substr($alphabet,$r,1)."陣",$body);
      }
      else if(preg_match('/-([A-E])/',$body,$match))
      {
        $max = strpos($alphabet,$match[1]);
        if ($group_len < 3)
        {
          $max = $group_len;
        }
        $r = rand(0,$max);
        $body = preg_replace('/-[A-E]/',substr($alphabet,$r,1)."陣",$body);
      }
      if (preg_match('/([A-E])陣/',$body,$match) && isset($members[$comment->getMemberId()]))
      {
        $members[$comment->getMemberId()]['h'] = $match[1];
      }
      if (preg_match('/(X|別)陣/',$body,$match) && isset($members[$comment->getMemberId()]))
      {
        $members[$comment->getMemberId()]['h'] = 'X';
      }
    }
    //シャッフル
    shuffle($members);
    $groups = array();
    //希望あり優先決定
    foreach ($members as $i => $member)
    {
      if (@$member['h'])
      {
        $h = strpos($alphabet,$member['h']);
        $groups[$h][] = $member;
        unset($members[$i]);
      }
    }
    $p = 0;
    foreach ($members as $member)
    {
      if (count(@$groups[$p]) >= 4){
        $p++;
      }
      $groups[$p][] = $member;
      if (count(@$groups[$p]) >= 4){
        $p++;
      }
    }

    //端数処理
    foreach ($groups as $i => $group)
    {
      if (count($group) <= 2)
      {
        $j = $i - 1;
        while (true)
        {
          if ($j < 0 || !@$groups[$j])
          {
            break;
          }
          switch (count($groups[$i]))
          {
            case 1:
              if (count($groups[$j]) >= 4)
              {
                $groups[$j][] = array_pop($groups[$i]);
                unset($groups[$i]);
                break 2;
              }
              break;
            case 2:
              if (count($groups[$j]) >= 4)
              {
                $groups[$j][] = array_pop($groups[$i]);
                break;
              }
              break;
          }
          $j--;
        }
      }
    }
    ksort($groups);
    //コメントとしてシャッフル結果を発表
    $result = "";
    foreach ($groups as $i => $group)
    {
      $result .= substr($alphabet,$i,1) . "\n";
      foreach ($group as $j => $member)
      {
        $result .= $member['nickname'] . "\n";
      }
    }
    $result .= "\n\n";
    $result .= Doctrine::getTable('SnsConfig')->get('oplunchrandomizerplugin_lr_footer','');

    //シャッフル結果をコメントに書き込む
    $comment = new CommunityEventComment();
    $comment->setCommunityEventId($target_event_id);
    $comment->setMemberId(Doctrine::getTable('SnsConfig')->get('oplunchrandomizerplugin_lr_from',1));
    $comment->setBody($result);
    $comment->save();
    Doctrine::getTable('SnsConfig')->set('target_lunch_event',null);

    $this->log2activity(Doctrine::getTable('SnsConfig')->get('oplunchrandomizerplugin_lr_from',1),'ランチメンバーシャッフル完了！');
  }
  private function kibouList4text($text){
    
  }
  private function test_createevent(){
    $event_date = strtotime('tomorrow');
    $title = date('m-d',$event_date) .  'のランチイベント';
    $body = Doctrine::getTable('SnsConfig')->get('oplunchrandomizerplugin_lr_body','none');
    $member_id = (int)Doctrine::getTable('SnsConfig')->get('oplunchrandomizerplugin_lr_from',null);
    $community_id = (int)Doctrine::getTable('SnsConfig')->get('oplunchrandomizerplugin_lr_community',null);

    //$member_id = 1;
    //$community_id = 1;

    //$line = exec($cmd);
    $event = new CommunityEvent();
    $event->setCommunityId($community_id);
    $event->setMemberId($member_id);
    $event->setName($title);
    $event->setBody($body);
    $event->setOpenDate(date("Y-m-d",$event_date));
    $event->save();

    $member = Doctrine::getTable('Member')->find($member_id);

    Doctrine::getTable('SnsConfig')->set('target_lunch_event',$event->id);

    $this->log2activity($member_id,'ランチイベントを作成！');
  }
  private function log2activity($id,$body){
    $act = new ActivityData();
    $act->setMemberId($id);
    $act->setBody($body);
    $act->setIsMobile(0);
    $act->save();
  }
}
