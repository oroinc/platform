<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Page;

use Oro\Bundle\DataGridBundle\Tests\Behat\Element\Grid;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Page;

class UserView extends Page
{
    /**
     * {@inheritdoc}
     */
    public function open(array $parameters = [])
    {
        $this->getMainMenu()->openAndClick('System/ User Management/ Users');

        /** @var Grid $grid */
        $grid = $this->elementFactory->createElement('Grid');
        $grid->clickActionLink($parameters['title'], 'View');
    }
}
