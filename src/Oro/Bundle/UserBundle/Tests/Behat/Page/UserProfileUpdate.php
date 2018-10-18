<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class UserProfileUpdate extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $userMenu = $this->elementFactory->createElement('UserMenu');
        $userMenu->find('css', '[data-toggle="dropdown"]')->click();

        $userMenu->clickLink('My User');
        $this->waitForAjax();

        $editButton = $this->elementFactory->createElement('Edit Button');
        $editButton->click();
        $this->waitForAjax();
    }
}
