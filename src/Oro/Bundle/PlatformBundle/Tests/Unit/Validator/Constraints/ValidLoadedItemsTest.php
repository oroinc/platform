<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\PlatformBundle\Validator\Constraints\ValidLoadedItems;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\NotNull;

class ValidLoadedItemsTest extends TestCase
{
    public function testThatConstraintsPropertyIsSet(): void
    {
        $childConstraint = new NotNull();
        $constraint = new ValidLoadedItems();
        $constraint->constraints = [$childConstraint];
        self::assertEquals([$childConstraint], $constraint->constraints);
    }
}
