<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class MenuUpdateTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testGetLinkAttributes(): void
    {
        $update = new MenuUpdate();
        self::assertSame([], $update->getLinkAttributes());
    }
}
