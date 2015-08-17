<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Groups
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium\Pages
 * @method Groups openGroups(string $bundlePath)
 * @method Group add()
 * @method Group open(array $filter)
 * {@inheritdoc}
 */
class Groups extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create Group']";
    const URL = 'user/group';


    public function entityNew()
    {
        return new Group($this->test);
    }

    public function entityView()
    {
        return new Group($this->test);
    }
}
