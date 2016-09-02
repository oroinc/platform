<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Model;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;

class MenuUpdateTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 42],
            ['key', 'page_wrapper'],
            ['parentId', 'page_container'],
            ['title', ''],
            ['menu', 'main_menu'],
            ['ownershipType', MenuUpdate::OWNERSHIP_GLOBAL],
            ['ownerId', 3],
            ['isActive', true],
            ['priority', 1],
        ];

        $this->assertPropertyAccessors(new MenuUpdate(), $properties);
    }
}
