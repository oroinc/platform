<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\DataLoaderInterface;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadEntitiesByDataLoader;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ParameterBag;

class LoadEntitiesByDataLoaderTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var DataLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dataLoader;

    /** @var LoadEntitiesByDataLoader */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dataLoader = $this->createMock(DataLoaderInterface::class);

        $this->processor = new LoadEntitiesByDataLoader($this->dataLoader);
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
        $serializedData = [['id' => 1, 'key' => 'val1'], ['id' => 2, 'key' => 'val2']];

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $config = new Config();
        $config->setDefinition($entityDefinitionConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $normalizationContext = [
            'action'      => $this->context->getAction(),
            'version'     => $this->context->getVersion(),
            'requestType' => $this->context->getRequestType(),
            'sharedData'  => $sharedData
        ];
        $this->dataLoader->expects(self::once())
            ->method('loadData')
            ->with(
                self::identicalTo($query),
                self::identicalTo($entityDefinitionConfig),
                $normalizationContext
            )
            ->willReturn($data);
        $this->dataLoader->expects(self::once())
            ->method('serializeData')
            ->with(
                $data,
                self::identicalTo($entityDefinitionConfig),
                $normalizationContext
            )
            ->willReturn($serializedData);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        $result = $this->context->getResult();
        self::assertEquals($serializedData, $result);
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessWhenHasMoreRequestedAndRecordsLimitNotExceeded(): void
    {
        $entityClass = Group::class;

        $sharedData = new ParameterBag();
        $sharedData->set('someKey', 'someSharedValue');
        $this->context->setSharedData($sharedData);

        $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');
        $query->setMaxResults(2);

        $data = [['id' => 1], ['id' => 2]];
        $serializedData = [['id' => 1, 'key' => 'val1'], ['id' => 2, 'key' => 'val2']];

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $entityDefinitionConfig->setHasMore(true);
        $config = new Config();
        $config->setDefinition($entityDefinitionConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $normalizationContext = [
            'action'      => $this->context->getAction(),
            'version'     => $this->context->getVersion(),
            'requestType' => $this->context->getRequestType(),
            'sharedData'  => $sharedData
        ];
        $this->dataLoader->expects(self::once())
            ->method('loadData')
            ->with(
                self::identicalTo($query),
                self::identicalTo($entityDefinitionConfig),
                $normalizationContext
            )
            ->willReturnCallback(function (QueryBuilder $qb) use ($data) {
                $qb->setMaxResults($qb->getMaxResults() + 1);

                return $data;
            });
        $this->dataLoader->expects(self::once())
            ->method('serializeData')
            ->with(
                $data,
                self::identicalTo($entityDefinitionConfig),
                $normalizationContext
            )
            ->willReturn($serializedData);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        $result = $this->context->getResult();
        self::assertEquals($serializedData, $result);
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }

    public function testProcessWhenHasMoreRequestedAndRecordsLimitExceeded(): void
    {
        $entityClass = Group::class;

        $sharedData = new ParameterBag();
        $sharedData->set('someKey', 'someSharedValue');
        $this->context->setSharedData($sharedData);

        $query = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');
        $query->setMaxResults(2);

        $data = [['id' => 1], ['id' => 2], ['id' => 3]];
        $truncatedData = [['id' => 1], ['id' => 2]];
        $serializedData = [['id' => 1, 'key' => 'val1'], ['id' => 2, 'key' => 'val2']];
        $resultData = $serializedData;
        $resultData[ConfigUtil::INFO_RECORD_KEY] = [ConfigUtil::HAS_MORE => true];

        $entityDefinitionConfig = new EntityDefinitionConfig();
        $entityDefinitionConfig->setHasMore(true);
        $config = new Config();
        $config->setDefinition($entityDefinitionConfig);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $normalizationContext = [
            'action'      => $this->context->getAction(),
            'version'     => $this->context->getVersion(),
            'requestType' => $this->context->getRequestType(),
            'sharedData'  => $sharedData
        ];
        $this->dataLoader->expects(self::once())
            ->method('loadData')
            ->with(
                self::identicalTo($query),
                self::identicalTo($entityDefinitionConfig),
                $normalizationContext
            )
            ->willReturnCallback(function (QueryBuilder $qb) use ($data) {
                $qb->setMaxResults($qb->getMaxResults() + 1);

                return $data;
            });
        $this->dataLoader->expects(self::once())
            ->method('serializeData')
            ->with(
                $truncatedData,
                self::identicalTo($entityDefinitionConfig),
                $normalizationContext
            )
            ->willReturn($serializedData);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        $result = $this->context->getResult();
        self::assertEquals($resultData, $result);
        self::assertEquals([ApiActionGroup::NORMALIZE_DATA], $this->context->getSkippedGroups());
    }
}
