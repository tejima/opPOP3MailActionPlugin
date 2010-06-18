<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opPOP3MailActionPlugin actions.
 *
 * @package    OpenPNE
 * @subpackage opPOP3MailActionPlugin
 * @author     Mamoru Tejima <tejima@tejimaya.com>
 * @version    SVN: $Id: actions.class.php 9301 2008-05-27 01:08:46Z dwhittle $
 */
class opPOP3MailActionPluginActions extends sfActions
{
 /**
  * Executes index action
  *
  * @param sfWebRequest $request A request object
  */
  public function executeIndex(sfWebRequest $request)
  {
    $this->form = new POP3MailActionPluginConfigForm();
    if ($request->isMethod(sfWebRequest::POST))
    {
      $this->form->bind($request->getParameter('pop3'));
      if ($this->form->isValid())
      {
        $this->form->save();
        $this->redirect('opPOP3MailActionPlugin/index');
      }
    }
  }
}
