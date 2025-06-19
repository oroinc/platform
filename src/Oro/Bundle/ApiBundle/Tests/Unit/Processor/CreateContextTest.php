<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use PHPUnit\Framework\TestCase;

class CreateContextTest extends TestCase
{
    public function testInitialExisting(): void
    {
        $context = new CreateContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );

        self::assertFalse($context->isExisting());
    }
}
