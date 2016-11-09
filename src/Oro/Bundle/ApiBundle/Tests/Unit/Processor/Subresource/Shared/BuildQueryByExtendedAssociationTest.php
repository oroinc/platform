<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\BuildQueryByExtendedAssociation;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class BuildQueryByExtendedAssociationTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    /** @var BuildQueryByExtendedAssociation */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        /** @var EntityClassResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var CriteriaConnector|\PHPUnit_Framework_MockObject_MockObject $criteriaConnector */
        $criteriaConnector = $this->getMockBuilder(CriteriaConnector::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new BuildQueryByExtendedAssociation(
            $this->doctrineHelper,
            $resolver,
            $criteriaConnector
        );
    }

    /**
     * @param string $entityShortClass
     *
     * @return string
     */
    protected function getEntityClass($entityShortClass)
    {
        return self::ENTITY_NAMESPACE . $entityShortClass;
    }

    public function testProcessWhenCriteriaAlreadyExist()
    {
        $this->context->setCriteria(new Criteria());

        $this->processor->process($this->context);

        $this->assertNull($this->context->getQuery());
    }

    public function testProcessWhenQueryAlreadyExist()
    {
        $qb = new QueryBuilder($this->em);
        $this->context->setQuery($qb);

        $this->processor->process($this->context);

        $this->assertNull($this->context->getCriteria());
        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessWhenConfigDoesNotExist()
    {
        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category');
        $this->context->setConfig(null);

        $this->processor->process($this->context);

        $this->assertNull($this->context->getCriteria());
        $this->assertNull($this->context->getQuery());
    }

    public function testProcessWhenParentConfigsDoesNotExist()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Owner';
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';

        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setParentClassName($parentClassName);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $parentClassName,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $this->context->getParentConfigExtras()
            )
            ->willReturn(new Config());

        $this->processor->process($this->context);

        $this->assertNull($this->context->getCriteria());
        $this->assertNull($this->context->getQuery());
    }

    public function testProcessWhenAssociationIsNotExtend()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Owner';
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';

        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setParentClassName($parentClassName);

        $parentConfigDefinition = new EntityDefinitionConfig();
        $parentConfigDefinition->addField('test_association')->setDataType('integer');
        $parentConfig = new Config();
        $parentConfig->setDefinition($parentConfigDefinition);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $parentClassName,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $this->context->getParentConfigExtras()
            )
            ->willReturn($parentConfig);
        $this->context->setAssociationName('test_association');
        $this->context->setIsCollection(false);

        $this->processor->process($this->context);

        $this->assertNull($this->context->getCriteria());
        $this->assertNull($this->context->getQuery());
    }

    public function testProcessWhenAssociationIsNotExtendManyToOne()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Owner';
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';

        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setParentClassName($parentClassName);

        $parentConfigDefinition = new EntityDefinitionConfig();
        $parentConfigDefinition->addField('test_association')->setDataType('association:manyToMany');
        $parentConfig = new Config();
        $parentConfig->setDefinition($parentConfigDefinition);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $parentClassName,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $this->context->getParentConfigExtras()
            )
            ->willReturn($parentConfig);
        $this->context->setAssociationName('test_association');
        $this->context->setIsCollection(false);

        $this->processor->process($this->context);

        $this->assertNull($this->context->getCriteria());
        $this->assertNull($this->context->getQuery());
    }

    public function testProcessForNotManageableEntity()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Owner';
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $this->notManageableClassNames = [$parentClassName];

        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setParentClassName($parentClassName);

        $parentConfigDefinition = new EntityDefinitionConfig();
        $parentConfigDefinition->addField('test_association')->setDataType('association:manyToOne');
        $parentConfig = new Config();
        $parentConfig->setDefinition($parentConfigDefinition);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $parentClassName,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $this->context->getParentConfigExtras()
            )
            ->willReturn($parentConfig);
        $this->context->setAssociationName('test_association');
        $this->context->setIsCollection(false);

        $this->processor->process($this->context);

        $this->assertNull($this->context->getCriteria());
        $this->assertNull($this->context->getQuery());
    }

    public function testProcessAssociationManyToOne()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Owner';
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product';
        $parentId = 123;

        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setParentClassName($parentClassName);
        $this->context->setParentId($parentId);

        $parentConfigDefinition = new EntityDefinitionConfig();
        $parentConfigDefinition->addField('test_association')->setDataType('association:manyToOne');
        $parentConfig = new Config();
        $parentConfig->setDefinition($parentConfigDefinition);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $parentClassName,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $this->context->getParentConfigExtras()
            )
            ->willReturn($parentConfig);
        $this->context->setAssociationName('test_association');
        $this->context->setIsCollection(false);

        $this->processor->process($this->context);

        $this->assertNotNull($this->context->getCriteria());
        $this->assertNotNull($this->context->getQuery());
        $this->assertSame(
            'SELECT e FROM Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product e WHERE e.id = :id',
            $this->context->getQuery()->getDql()
        );
        $this->assertEquals(
            $parentId,
            $this->context->getQuery()->getParameter('id')->getValue()
        );
    }
}
