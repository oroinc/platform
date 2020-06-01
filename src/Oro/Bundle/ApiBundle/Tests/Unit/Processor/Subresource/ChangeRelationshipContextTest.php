<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class ChangeRelationshipContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataProvider */
    private $metadataProvider;

    /** @var ChangeRelationshipContext */
    private $context;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new ChangeRelationshipContext($this->configProvider, $this->metadataProvider);
    }

    public function testParentEntity()
    {
        self::assertNull($this->context->getParentEntity());
        self::assertFalse($this->context->hasParentEntity());

        $entity = new \stdClass();
        $this->context->setParentEntity($entity);
        self::assertSame($entity, $this->context->getParentEntity());
        self::assertTrue($this->context->hasParentEntity());

        $this->context->setParentEntity(null);
        self::assertNull($this->context->getParentEntity());
        self::assertFalse($this->context->hasParentEntity());
    }
}
