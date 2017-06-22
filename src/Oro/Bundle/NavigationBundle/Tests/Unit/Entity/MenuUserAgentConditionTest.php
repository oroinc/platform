<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Entity\MenuUserAgentCondition;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class MenuUserAgentConditionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testGetExtras()
    {
        $properties = [
            ['id', '123'],
            ['menuUpdate', new MenuUpdate()],
            ['conditionGroupIdentifier', 1],
            ['operation', 'does not contain'],
            ['value', 'test'],
        ];

        $entity = new MenuUserAgentCondition();
        static::assertPropertyAccessors($entity, $properties);
    }
}
