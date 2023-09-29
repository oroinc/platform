<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class CreateContextTest extends \PHPUnit\Framework\TestCase
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
