<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\BuildQueryForExtendedManyToOneAssociation;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;

class BuildQueryForExtendedManyToOneAssociationTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    /** @var BuildQueryForExtendedManyToOneAssociation */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new BuildQueryForExtendedManyToOneAssociation($this->doctrineHelper);
    }

    public function testProcessWhenQueryAlreadyExist()
    {
        $qb = new QueryBuilder($this->em);

        $this->context->setQuery($qb);
        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForNotExtendedAssociation()
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField('test_association')->setDataType('integer');

        $this->context->setParentClassName(Entity\Product::class);
        $this->context->setParentConfig($parentConfig);
        $this->context->setAssociationName('test_association');
        $this->processor->process($this->context);

        $this->assertNull($this->context->getQuery());
    }

    public function testProcessForExtendedAssociationButItIsNotManyToOne()
    {
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField('test_association')->setDataType('association:manyToMany');

        $this->context->setParentClassName(Entity\Product::class);
        $this->context->setParentConfig($parentConfig);
        $this->context->setAssociationName('test_association');
        $this->processor->process($this->context);

        $this->assertNull($this->context->getQuery());
    }

    public function testProcessForExtendedManyToOneAssociation()
    {
        $parentClassName = Entity\Product::class;
        $parentId = 123;

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField('test_association')->setDataType('association:manyToOne');

        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);
        $this->context->setParentConfig($parentConfig);
        $this->context->setAssociationName('test_association');
        $this->processor->process($this->context);

        $this->assertNotNull($this->context->getQuery());
        $this->assertEquals(
            'SELECT e FROM ' . $parentClassName . ' e WHERE e.id = :id',
            $this->context->getQuery()->getDql()
        );
        $this->assertSame(
            $parentId,
            $this->context->getQuery()->getParameter('id')->getValue()
        );
    }
}
