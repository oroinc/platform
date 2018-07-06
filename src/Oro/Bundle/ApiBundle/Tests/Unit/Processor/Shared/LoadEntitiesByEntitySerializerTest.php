<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadEntitiesByEntitySerializer;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;
use Oro\Component\EntitySerializer\EntitySerializer;

class LoadEntitiesByEntitySerializerTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntitySerializer */
    private $serializer;

    /** @var LoadEntitiesByEntitySerializer */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->serializer = $this->createMock(EntitySerializer::class);

        $this->processor = new LoadEntitiesByEntitySerializer($this->serializer);
    }

    public function testProcessWithResult()
    {
        $resultEntity = new Product();

        $this->context->setResult($resultEntity);
        $this->processor->process($this->context);

        self::assertSame($resultEntity, $this->context->getResult());
    }

    public function testProcessWithUnsupportedQuery()
    {
        self::assertFalse($this->context->hasResult());

        $this->context->setQuery(new \stdClass());
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWithoutConfig()
    {
        $entityClass = Group::class;

        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn(new Config());

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcess()
    {
        $entityClass = Group::class;

        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');

        $data = [new Group()];

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
                    'requestType' => $this->context->getRequestType()
                ]
            )
            ->willReturn($data);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        $result = $this->context->getResult();
        self::assertEquals($data, $result);
        self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
    }
}
