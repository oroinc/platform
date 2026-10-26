<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption as EntityEnumOption;
use PHPUnit\Framework\TestCase;

class EnumOptionTest extends TestCase
{
    public function testConstructorUsesEntityEnumOptionBehavior(): void
    {
        $option = new EnumOption('product_color', 'Red', 'red', 10, true);

        self::assertInstanceOf(EntityEnumOption::class, $option);
        self::assertEquals('product_color.red', $option->getId());
        self::assertEquals('product_color', $option->getEnumCode());
        self::assertEquals('red', $option->getInternalId());
        self::assertEquals('Red', $option->getName());
        self::assertEquals(10, $option->getPriority());
        self::assertTrue($option->isDefault());
    }
}
