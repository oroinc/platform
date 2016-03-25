<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadEntitiesByEntitySerializer;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorOrmRelatedTestCase;

class LoadEntitiesByEntitySerializerTest extends GetListProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $serializer;

    /** @var LoadEntitiesByEntitySerializer */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->serializer = $this->getMockBuilder('Oro\Component\EntitySerializer\EntitySerializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadEntitiesByEntitySerializer($this->serializer);
    }

    public function testProcessWithResult()
    {
        $resultEntity = new Product();

        $this->context->setResult($resultEntity);
        $this->processor->process($this->context);

        $this->assertSame($resultEntity, $this->context->getResult());
    }

    public function testProcessWithUnsupportedQuery()
    {
        $this->assertFalse($this->context->hasResult());

        $this->context->setQuery(new \stdClass());
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasResult());
    }

    public function testProcessWithoutConfig()
    {
        $entityClass = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group';

        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn(new Config());

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasResult());
    }

    public function testProcess()
    {
        $entityClass = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group';

        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');

        $data = [new Group()];

        $config = new Config();

        $config->setDefinition(new EntityDefinitionConfig());
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($query)
            ->willReturn($data);

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        $result = $this->context->getResult();
        $this->assertEquals($data, $result);
        $this->assertEquals(['normalize_data'], $this->context->getSkippedGroups());
    }
}
