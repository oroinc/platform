<?php

namespace Oro\Bundle\SecurityBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

/**
 * Class AclCheck
 *
 * @package Oro\Bundle\SecurityBundle\Tests\Selenium\Pages
 * @method AclCheck openAclCheck(string $bundlePath)
  */
class AclCheck extends AbstractPage
{
    public function assertAcl($url, $title = '403 - Forbidden')
    {
        $this->test->url($url);
        $this->waitPageToLoad();
        $this->assertTitle($title, 'Page title is not that was expected');
        return $this;
    }
}
