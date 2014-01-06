<?php

namespace Oro\Bundle\TestFrameworkBundle\Pages\Objects;

use Oro\Bundle\TestFrameworkBundle\Pages\Page;

class OroInstall extends Page
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
        $this->test->moveto($this->byXpath("//button['Begin Installation'=normalize-space(.)]"));
        $this->test->byXpath("//button['Begin Installation'=normalize-space(.)]")->click();
        $this->waitPageToLoad();
        $this->assertTitle('Oro Application installation');
        return new OroRequirements($this->test);
    }
}
