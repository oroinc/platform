<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Model;

use Oro\Bundle\LocaleBundle\Model\FallbackType;
use PHPUnit\Framework\TestCase;

class FallbackTypeTest extends TestCase
{
    public function testConstructAndGetType(): void
    {
        $type = FallbackType::SYSTEM;
        $fallbackType = new FallbackType($type);

        self::assertEquals($type, $fallbackType->getType());
    }
}
