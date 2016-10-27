<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class UserIndex extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $this->getMainMenu()->openAndClick('System/User Management/Users');
    }
}
