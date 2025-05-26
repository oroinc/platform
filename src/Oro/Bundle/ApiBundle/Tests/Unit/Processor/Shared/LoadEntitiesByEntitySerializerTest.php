<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadEntitiesByEntitySerializer;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Component\ChainProcessor\ParameterBag;
use Oro\Component\EntitySerializer\ConfigUtil;
use Oro\Component\EntitySerializer\EntitySerializer;
use PHPUnit\Framework\MockObject\MockObject;

class LoadEntitiesByEntitySerializerTest extends GetListProcessorOrmRelatedTestCase
{
    private EntitySerializer&MockObject $serializer;
    private LoadEntitiesByEntitySerializer $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = $this->createMock(EntitySerializer::class);

        $this->processor = new LoadEntitiesByEntitySerializer($this->serializer);
    }

    public function testProcessWithResult(): void
    {
        $resultEntity = new Product();

        $this->context->setResult($resultEntity);
        $this->processor->process($this->context);

        self::assertSame($resultEntity, $this->context->getResult());
    }

    public function testProcessWithUnsupportedQuery(): void
    {
        self::assertFalse($this->context->hasResult());

        $this->context->setQuery(new \stdClass());
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWithoutConfig(): void
    {
        $entityClass = Group::class;

        $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn(new Config());

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcess(): void
    {
        $entityClass = Group::class;

        $sharedData = new ParameterBag();
        $sharedData->set('someKey', 'someSharedValue');
        $this->context->setSharedData($sharedData);

        $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');

        $data = [['id' => 1], ['id' => 2]];

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $config = new Config();
        $config->setDefinition($entityDefinitionConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $this->serializer->expects(self::once())
            ->method('serialize')
            ->with(
                self::identicalTo($query),
                self::identicalTo($entityDefinitionConfig),
                [
                    'action'      => $this->context->getAction(),
                    'version'     => $this->context->getVersion(),
                    'requestType' => $this->context->getRequestType(),
                    'sharedData'  => $sharedData
                ]
            )
            ->willReturn($data);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        $result = $this->context->getResult();
        self::assertEquals($data, $result);
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessWithEntityIds(): void
    {
        $entityClass = Group::class;

        $sharedData = new ParameterBag();
        $sharedData->set('someKey', 'someSharedValue');
        $this->context->setSharedData($sharedData);

        $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $entityDefinitionConfig->setIdentifierFieldNames(['id']);
        $config = new Config();
        $config->setDefinition($entityDefinitionConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $this->serializer->expects(self::once())
            ->method('serialize')
            ->with(
                self::identicalTo($query),
                self::identicalTo($entityDefinitionConfig),
                [
                    'action'      => $this->context->getAction(),
                    'version'     => $this->context->getVersion(),
                    'requestType' => $this->context->getRequestType(),
                    'sharedData'  => $sharedData
                ]
            )
            ->willReturn([['id' => 1], ['id' => 2], ['id' => 3]]);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->context->set(LoadEntitiesByEntitySerializer::ENTITY_IDS, [2, 3, 1]);
        $this->processor->process($this->context);

        $result = $this->context->getResult();
        self::assertEquals([['id' => 2], ['id' => 3], ['id' => 1]], $result);
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessWithEntityIdsAndHasMore(): void
    {
        $entityClass = Group::class;

        $sharedData = new ParameterBag();
        $sharedData->set('someKey', 'someSharedValue');
        $this->context->setSharedData($sharedData);

        $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');
        $query->setMaxResults(2);

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $entityDefinitionConfig->setIdentifierFieldNames(['id']);
        $entityDefinitionConfig->setHasMore(true);
        $config = new Config();
        $config->setDefinition($entityDefinitionConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $this->serializer->expects(self::once())
            ->method('serialize')
            ->with(
                self::identicalTo($query),
                self::identicalTo($entityDefinitionConfig),
                [
                    'action'      => $this->context->getAction(),
                    'version'     => $this->context->getVersion(),
                    'requestType' => $this->context->getRequestType(),
                    'sharedData'  => $sharedData
                ]
            )
            ->willReturn([['id' => 1], ['id' => 2], ['id' => 3]]);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->context->set(LoadEntitiesByEntitySerializer::ENTITY_IDS, [2, 3, 1]);
        $this->processor->process($this->context);

        $result = $this->context->getResult();
        self::assertEquals(
            [['id' => 2], ['id' => 3], ConfigUtil::INFO_RECORD_KEY => [ConfigUtil::HAS_MORE => true]],
            $result
        );
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessWithEntityIdsAndForEntityWithCompositeIdentifier(): void
    {
        $entityClass = Group::class;

        $sharedData = new ParameterBag();
        $sharedData->set('someKey', 'someSharedValue');
        $this->context->setSharedData($sharedData);

        $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $entityDefinitionConfig->setIdentifierFieldNames(['id1', 'id2']);
        $config = new Config();
        $config->setDefinition($entityDefinitionConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $this->serializer->expects(self::never())
            ->method('serialize');

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->context->set(LoadEntitiesByEntitySerializer::ENTITY_IDS, [2, 3, 1]);
        try {
            $this->processor->process($this->context);
            self::fail(sprintf('Expected %s', RuntimeException::class));
        } catch (RuntimeException $e) {
            self::assertEquals('The entity must have one identifier field.', $e->getMessage());
        }

        self::assertFalse($this->context->hasResult());
        self::assertFalse($this->context->hasSkippedGroups());
    }
}
