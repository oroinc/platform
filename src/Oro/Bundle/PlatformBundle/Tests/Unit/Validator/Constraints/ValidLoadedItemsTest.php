<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\PlatformBundle\Validator\Constraints\ValidLoadedItems;
use Symfony\Component\Validator\Constraints\NotNull;

class ValidLoadedItemsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTargets()
    {
        $constraint = new ValidLoadedItems();
        self::assertEquals('property', $constraint->getTargets());
    }

    public function testThatConstraintsPropertyIsSet()
    {
        $childConstraint = new NotNull();
        $constraint = new ValidLoadedItems();
        $constraint->constraints = [$childConstraint];
        self::assertEquals([$childConstraint], $constraint->constraints);
    }
}
