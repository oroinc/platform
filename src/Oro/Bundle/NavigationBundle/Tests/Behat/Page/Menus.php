<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class Menus extends Page
{
    #[\Override]
    public function open(array $parameters = [])
    {
        $this->getMainMenu()->openAndClick('System/Menus');
    }
}
