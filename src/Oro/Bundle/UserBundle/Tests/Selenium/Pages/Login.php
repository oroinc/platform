<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

class Login extends AbstractPage
{
    public function __construct($testCase, $args = array('url' => '/'))
    {
        if (array_key_exists('url', $args)) {
            $this->redirectUrl = $args['url'];
        }
        parent::__construct($testCase);

        if (array_key_exists('remember', $args)) {
            $this->test->byXpath("//*[starts-with(@id,'remember_me')]")->click();
        }

        $this->username = $this->test->byXpath("//*[starts-with(@id,'prependedInput')]");
        $this->password = $this->test->byXpath("//*[starts-with(@id,'prependedInput2')]");
    }

    public function setUsername($value)
    {
        $this->username->clear();
        $this->username->value($value);
        return $this;
    }

    public function setPassword($value)
    {
        $this->password->clear();
        $this->password->value($value);
        return $this;
    }

    public function submit()
    {
        $this->test->byXpath("//*[starts-with(@id,'_submit')]")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    public function loggedIn()
    {
        if (strtolower($this->title()) == 'login' or $this->url()=='user/login') {
            return false;
        } else {
            return true;
        }
    }
}
