<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class Menus extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $this->getMainMenu()->openAndClick('System/Menus');
    }
}
