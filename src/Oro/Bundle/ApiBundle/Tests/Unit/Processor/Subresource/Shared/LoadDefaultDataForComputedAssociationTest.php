<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\LoadDefaultDataForComputedAssociation;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class LoadDefaultDataForComputedAssociationTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    private LoadDefaultDataForComputedAssociation $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new LoadDefaultDataForComputedAssociation();
    }

    public function testProcessWhenResultIsAlreadySet(): void
    {
        $data = ['key' => 'val'];

        $this->context->setResult($data);
        $this->context->setAssociationName('testAssociation');
        $this->processor->process($this->context);

        self::assertSame($data, $this->context->getResult());
    }

    public function testProcessWhenQueryExists(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        $this->context->setQuery($qb);
        $this->context->setAssociationName('testAssociation');
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWhenNoAssociationConfig(): void
    {
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->context->setAssociationName('testAssociation');
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessForNotComputedAssociation(): void
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField('testAssociation');

        $this->context->setParentConfig($parentConfig);
        $this->context->setAssociationName('testAssociation');
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessForComputedToOneAssociation(): void
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField('testAssociation')->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $this->context->setParentConfig($parentConfig);
        $this->context->setAssociationName('testAssociation');
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasResult());
        self::assertNull($this->context->getResult());
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessForComputedToManyAssociation(): void
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField('testAssociation')->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);

        $this->context->setParentConfig($parentConfig);
        $this->context->setAssociationName('testAssociation');
        $this->context->setIsCollection(true);
        $this->processor->process($this->context);

        self::assertTrue($this->context->hasResult());
        self::assertSame([], $this->context->getResult());
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }
}
