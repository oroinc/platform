<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\CustomizeLoadedDataConfigExtra;
use Oro\Bundle\ApiBundle\Config\DataTransformersConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetRelationship\GetRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\BuildQueryByExtendedAssociation;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\LoadEntityByExtendedAssociation;

use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Attachment;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class LoadEntityByExtendedAssociationTest extends GetSubresourceProcessorOrmRelatedTestCase
{
    const ENTITY_NAMESPACE = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\\';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $serializer;

    /** @var LoadEntityByExtendedAssociation */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->serializer = $this->getMockBuilder('Oro\Component\EntitySerializer\EntitySerializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadEntityByExtendedAssociation(
            $this->serializer,
            $this->configProvider,
            $this->metadataProvider
        );
    }

    public function testProcessWhenResultIsExist()
    {
        $result = ['result' => true];
        $this->context->setResult($result);

        $this->processor->process($this->context);

        $this->assertSame($result, $this->context->getResult());
    }

    public function testProcessForUnsupportedQuery()
    {
        $this->context->setQuery(new \stdClass());

        $this->processor->process($this->context);

        $this->assertNull($this->context->getResult());
    }

    public function testProcessWhenConfigDoesNotExist()
    {
        $this->context->setQuery(new QueryBuilder($this->em));

        $this->context->setClassName('Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account');
        $this->context->setConfig(null);

        $this->processor->process($this->context);

        $this->assertNull($this->context->getResult());
    }

    public function testProcessWhenParentConfigsDoesNotExist()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account';
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Attachment';

        $this->context->setQuery(new QueryBuilder($this->em));
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

        $this->assertNull($this->context->getResult());
    }

    public function testProcessWhenAssociationIsNotExtend()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account';
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Attachment';

        $this->context->setQuery(new QueryBuilder($this->em));
        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setParentClassName($parentClassName);

        $parentConfigDefinition = new EntityDefinitionConfig();
        $parentConfigDefinition->addField('target')->setDataType('integer');
        $parentConfig = new Config();
        $parentConfig->setDefinition($parentConfigDefinition);
        $this->context->setAssociationName('target');
        $this->context->setIsCollection(false);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $parentClassName,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $this->context->getParentConfigExtras()
            )
            ->willReturn($parentConfig);

        $this->processor->process($this->context);

        $this->assertNull($this->context->getResult());
    }

    public function testProcessWhenAssociationIsNotExtendManyToOne()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account';
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Attachment';

        $this->context->setQuery(new QueryBuilder($this->em));
        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setParentClassName($parentClassName);
        $this->context->setAssociationName('target');
        $this->context->setIsCollection(false);

        $parentConfigDefinition = new EntityDefinitionConfig();
        $parentConfigDefinition->addField('target')->setDataType('association:manyToMany');
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

        $this->processor->process($this->context);

        $this->assertNull($this->context->getResult());
    }

    public function testProcessWithNullSourceResult()
    {
        $className = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account';
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Attachment';

        $query = $this->getMockBuilder('\Doctrine\ORM\AbstractQuery')
            ->setMethods(['getOneOrNullResult'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $qb = $this->getQueryBuilderMock();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->context->setQuery($qb);
        $this->context->setClassName($className);
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->context->setParentClassName($parentClassName);
        $this->context->setAssociationName('target');
        $this->context->setIsCollection(false);

        $parentConfigDefinition = new EntityDefinitionConfig();
        $parentConfigDefinition->addField('target')->setDataType('association:manyToOne');
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

        $this->processor->process($this->context);

        $this->assertNull($this->context->getResult());
    }

    public function testProcessWithSourceResultButEmptySerializerResult()
    {
        $entityClassName = 'Oro\Bundle\ApiBundle\Model\EntityIdentifier';
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Attachment';

        $entity = new Account();
        $entity->setId(123);
        $entity->setName('testAccount');

        $parentEntity = new Attachment('testAttachment');
        $parentEntity->setAccount($entity);

        $query = $this->getMockBuilder('\Doctrine\ORM\AbstractQuery')
            ->setMethods(['getOneOrNullResult'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($parentEntity);
        $qb = $this->getQueryBuilderMock();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $config = new EntityDefinitionConfig();

        $this->context->setQuery($qb);
        $this->context->setClassName($entityClassName);
        $this->context->setConfig($config);
        $this->context->setParentClassName($parentClassName);
        $this->context->setAssociationName('target');
        $this->context->setIsCollection(false);

        $parentConfigDefinition = new EntityDefinitionConfig();
        $parentConfigDefinition->addField('target')->setDataType('association:manyToOne');
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

        $this->metadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->serializer->expects($this->once())
            ->method('serializeEntities')
            ->with([$entity], get_class($entity), $config)
            ->willReturn([]);

        $this->processor->process($this->context);

        $this->assertNull($this->context->getResult());
        $this->assertSame(['normalize_data'], $this->context->getSkippedGroups());
    }

    public function testProcessForGetRelationshipContext()
    {
        $entityClassName = 'Oro\Bundle\ApiBundle\Model\EntityIdentifier';
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Attachment';

        $entity = new Account();
        $entity->setId(123);
        $entity->setName('testAccount');

        $parentEntity = new Attachment('testAttachment');
        $parentEntity->setAccount($entity);

        $query = $this->getMockBuilder('\Doctrine\ORM\AbstractQuery')
            ->setMethods(['getOneOrNullResult'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($parentEntity);
        $qb = $this->getQueryBuilderMock();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $config = new EntityDefinitionConfig();

        $this->context = new GetRelationshipContext($this->configProvider, $this->metadataProvider);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $this->context->setQuery($qb);
        $this->context->setClassName($entityClassName);
        $this->context->setConfig($config);
        $this->context->setParentClassName($parentClassName);
        $this->context->setAssociationName('target');
        $this->context->setIsCollection(false);

        $parentConfigDefinition = new EntityDefinitionConfig();
        $parentConfigDefinition->addField('target')->setDataType('association:manyToOne');
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

        $this->metadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->serializer->expects($this->once())
            ->method('serializeEntities')
            ->with([$entity], get_class($entity), $config)
            ->willReturn([
                0 => [
                    '__class__' => get_class($entity),
                    'id' => 123
                ]
            ]);

        $this->processor->process($this->context);

        $this->assertNotNull($this->context->getResult());
        $this->assertSame(
            [
                '__class__' => get_class($entity),
                'id' => 123
            ],
            $this->context->getResult()
        );
        $this->assertSame(['normalize_data'], $this->context->getSkippedGroups());
    }

    public function testProcessForGetSubresourceContext()
    {
        $entityClassName = 'Oro\Bundle\ApiBundle\Model\EntityIdentifier';
        $parentClassName = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Attachment';

        $entity = new Account();
        $entity->setId(123);
        $entity->setName('testAccount');

        $parentEntity = new Attachment('testAttachment');
        $parentEntity->setAccount($entity);

        $query = $this->getMockBuilder('\Doctrine\ORM\AbstractQuery')
            ->setMethods(['getOneOrNullResult'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $query->expects($this->once())->method('getOneOrNullResult')->willReturn($parentEntity);
        $qb = $this->getQueryBuilderMock();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $config = new EntityDefinitionConfig();

        $this->context = new GetSubresourceContext($this->configProvider, $this->metadataProvider);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $this->context->setQuery($qb);
        $this->context->setClassName($entityClassName);
        $this->context->setConfig($config);
        $this->context->setParentClassName($parentClassName);
        $this->context->setAssociationName('target');
        $this->context->setIsCollection(false);

        $parentConfigDefinition = new EntityDefinitionConfig();
        $parentConfigDefinition->addField('target')->setDataType('association:manyToOne');
        $parentConfig = new Config();
        $parentConfig->setDefinition($parentConfigDefinition);

        $targetConfigDefinition = new EntityDefinitionConfig();
        $targetConfigDefinition->addField('name')->setDataType('string');
        $targetConfig = new Config();
        $targetConfig->setDefinition($targetConfigDefinition);

        $this->configProvider->expects($this->at(0))->method('getConfig')
            ->with(
                $parentClassName,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $this->context->getParentConfigExtras()
            )
            ->willReturn($parentConfig);
        $this->configProvider->expects($this->at(1))->method('getConfig')
            ->with(
                get_class($entity),
                $this->context->getVersion(),
                $this->context->getRequestType(),
                [
                    new EntityDefinitionConfigExtra(ApiActions::GET),
                    new CustomizeLoadedDataConfigExtra(),
                    new DataTransformersConfigExtra()
                ]
            )
            ->willReturn($targetConfig);
        $this->configProvider->expects($this->exactly(2))->method('getConfig');

        $metadata = new EntityMetadata();
        $metadata->setClassName(get_class($entity));
        $metadata->setInheritedType(false);
        $metadata->setHasIdentifierGenerator(true);
        $fieldMetadata = new FieldMetadata();
        $fieldMetadata->setName('name');
        $metadata->addField($fieldMetadata);

        $this->metadataProvider->expects($this->once())->method('getMetadata')
            ->with(
                get_class($entity),
                $this->context->getVersion(),
                $this->context->getRequestType(),
                $targetConfig->getDefinition()
            )
            ->willReturn($metadata);

        $this->serializer->expects($this->once())->method('serializeEntities')
            ->with([$entity], get_class($entity), $targetConfig->getDefinition())
            ->willReturn(
                [
                    0 => ['id' => 123, 'name' => 'testAccount']
                ]
            );

        $this->processor->process($this->context);

        $this->assertSame($metadata, $this->context->getMetadata());
        $this->assertNotNull($this->context->getResult());
        $this->assertSame(
            ['id' => 123, 'name' => 'testAccount'],
            $this->context->getResult()
        );
        $this->assertSame(['normalize_data'], $this->context->getSkippedGroups());
    }
}
