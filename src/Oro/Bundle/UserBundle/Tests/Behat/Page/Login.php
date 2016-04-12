<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Page;

use SensioLabs\Behat\PageObjectExtension\PageObject\Page;

class Login extends Page
{
    /**
     * @var string $path
     */
    protected $path = '/user/login';

    public function login($username, $password)
    {
        $this->open();
        $loginForm = $this->getElement('Login form');
        $loginForm->fillField('_username', 'admin');
        $loginForm->fillField('_password', 'admin');
        $loginForm->pressButton('_submit');

        return $this->getPage('Dashboard');
    }
}
