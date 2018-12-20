<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class UserEmails extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $userMenu = $this->elementFactory->createElement('UserMenu');
        $userMenu->find('css', '[data-toggle="dropdown"]')->click();

        $userMenu->clickLink('My Emails');
    }
}
