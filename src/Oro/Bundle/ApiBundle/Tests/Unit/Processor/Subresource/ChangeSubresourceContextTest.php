<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class ChangeSubresourceContextTest extends \PHPUnit\Framework\TestCase
{
    public function testInitialExisting(): void
    {
        $context = new ChangeSubresourceContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );

        self::assertTrue($context->isExisting());
    }
}
