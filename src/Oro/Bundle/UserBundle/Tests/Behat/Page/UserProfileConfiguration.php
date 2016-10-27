<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class UserProfileConfiguration extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $userMenu = $this->elementFactory->createElement('UserMenu');
        $userMenu->find('css', 'i.icon-sort-down')->click();

        $userMenu->clickLink('My Configuration');
    }
}
