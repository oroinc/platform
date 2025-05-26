<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SingleItemContextTest extends TestCase
{
    private ConfigProvider&MockObject $configProvider;
    private MetadataProvider&MockObject $metadataProvider;
    private SingleItemContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new SingleItemContext($this->configProvider, $this->metadataProvider);
    }

    public function testId(): void
    {
        self::assertNull($this->context->getId());

        $id = 'test';
        $this->context->setId($id);
        self::assertSame($id, $this->context->getId());

        $this->context->setId(null);
        self::assertNull($this->context->getId());
    }
}
