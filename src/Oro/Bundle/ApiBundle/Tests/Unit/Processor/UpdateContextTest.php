<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class UpdateContextTest extends \PHPUnit\Framework\TestCase
{
    public function testInitialExisting(): void
    {
        $context = new UpdateContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );

        self::assertTrue($context->isExisting());
    }
}
