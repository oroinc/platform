<?php

namespace Oro\Bundle\UserBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Groups
 *
 * @package Oro\Bundle\UserBundle\Tests\Selenium\Pages
 * @method Groups openGroups() openGroups(string)
 * @method Group add add()
 * @method Group open open()
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
