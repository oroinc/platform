<?php

namespace Oro\Bundle\SecurityBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

/**
 * Class AclCheck
 *
 * @package Oro\Bundle\SecurityBundle\Tests\Selenium\Pages
 * @method AclCheck openAclCheck() openAclCheck(string)
  */
class AclCheck extends AbstractPage
{
    public function __construct($testCase)
    {
        parent::__construct($testCase);
    }

    public function assertAcl($url, $title = '403 - Forbidden')
    {
        $this->test->url($url);
        $this->waitPageToLoad();
        $this->assertTitle($title, 'Page title is not that was expected');
        return $this;
    }
}
