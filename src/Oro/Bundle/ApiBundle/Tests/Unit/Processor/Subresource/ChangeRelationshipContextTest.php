<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource;

use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class ChangeRelationshipContextTest extends \PHPUnit\Framework\TestCase
{
    private ChangeRelationshipContext $context;

    protected function setUp(): void
    {
        $this->context = new ChangeRelationshipContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
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
