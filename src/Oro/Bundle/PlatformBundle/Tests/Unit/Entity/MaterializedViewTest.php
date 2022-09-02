<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Entity;

use Oro\Bundle\PlatformBundle\Entity\MaterializedView;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class MaterializedViewTest extends \PHPUnit\Framework\TestCase
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
