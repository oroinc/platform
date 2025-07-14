<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Entity;

use Oro\Bundle\PlatformBundle\Entity\MaterializedView;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class MaterializedViewTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        self::assertPropertyAccessors(
            new MaterializedView(),
            [
                ['name', 'sample-name'],
                ['withData', false],
                ['createdAt', new \DateTime()],
                ['updatedAt', new \DateTime()],
            ]
        );
    }
}
