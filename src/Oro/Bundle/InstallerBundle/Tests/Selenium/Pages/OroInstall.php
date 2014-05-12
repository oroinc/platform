<?php

namespace Oro\Bundle\InstallerBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

/**
 * Class OroInstall
 *
 * @package Oro\Bundle\InstallerBundle\Tests\Selenium\Pages
 */
class OroInstall extends AbstractPage
{
    public function __construct($testCase, $args = array('url' => '/install.php'))
    {
        if (array_key_exists('url', $args)) {
            $this->redirectUrl = $args['url'];
        }
        parent::__construct($testCase);
    }

    public function beginInstallation()
    {
        if ($this->isElementPresent("//button['Begin Installation'=normalize-space(.)]") &&
            $this->test->byXpath("//button['Begin Installation'=normalize-space(.)]")->displayed()
        ) {
            $this->test->moveto($this->test->byXpath("//button['Begin Installation'=normalize-space(.)]"));
            $this->test->byXpath("//button['Begin Installation'=normalize-space(.)]")->click();
        }
        $this->assertTitle('Oro Application installation');
        return new OroRequirements($this->test);
    }
}
