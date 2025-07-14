<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class AbstractExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface(): void
    {
        $this->assertClassImplements(ExtensionInterface::class, AbstractExtension::class);
    }
}
