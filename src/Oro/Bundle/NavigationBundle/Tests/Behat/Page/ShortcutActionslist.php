<?php

namespace Oro\Bundle\NavigationBundle\Tests\Behat\Page;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class ShortcutActionslist extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $documentElement = $this->elementFactory->getPage();
        $documentElement->clickLink('Shortcuts');
        $documentElement->clickLink('See full list');
    }
}
