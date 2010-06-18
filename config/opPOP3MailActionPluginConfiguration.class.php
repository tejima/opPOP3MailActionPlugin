<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opOpenSocialPluginConfiguration
 *
 * @package    opOpenSocialPlugin
 * @subpackage config
 * @author     Mamoru Tejima <tejima@tejimaya.com>
 */
class opPOP3MailActionPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    sfToolkit::addIncludePath(array(
      $this->rootDir.'/../../lib/vendor/',
    ));
  }
}
