<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class MenuUpdateTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testGetExtras()
    {
        $priority = 10;

        $update = new MenuUpdate();
        $update->setPriority($priority);
        $update->setDivider(true);
        $update->setIcon('test-icon');

        $this->assertEquals(
            [
                'position' => $priority,
                'divider' => true,
                'icon' => 'test-icon',
                'translate_disabled' => false
            ],
            $update->getExtras()
        );
    }
}
