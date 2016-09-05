<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use Oro\Bundle\NavigationBundle\Entity\AbstractMenuUpdate;

class AbstractMenuUpdateTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 42],
            ['key', 'page_wrapper'],
            ['parentKey', 'page_container'],
            ['uri', 'uri'],
            ['menu', 'main_menu'],
            ['ownershipType', AbstractMenuUpdate::OWNERSHIP_GLOBAL],
            ['ownerId', 3],
            ['active', true],
            ['priority', 1],
        ];

        $model = $this->getMockForAbstractClass('Oro\Bundle\NavigationBundle\Entity\AbstractMenuUpdate');

        $this->assertPropertyAccessors($model, $properties);
    }
}
