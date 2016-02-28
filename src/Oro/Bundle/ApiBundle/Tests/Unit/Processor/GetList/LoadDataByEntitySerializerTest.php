<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\GetList\LoadDataByEntitySerializer;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\LoadDataByEntitySerializerTestCase;

class LoadDataByEntitySerializerTest extends LoadDataByEntitySerializerTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->processor = new LoadDataByEntitySerializer($this->serializer);
        $this->context   = new GetListContext($this->configProvider, $this->metadataProvider);
    }

    public function testProcess()
    {
        $data   = [new Group()];
        $config = new Config();
        $config->setDefinition(new EntityDefinitionConfig());
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);
        $entityClass = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group';
        $this->context->setClassName($entityClass);
        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');
        $this->context->setQuery($query);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($query)
            ->willReturn($data);

        $this->assertEquals([], $this->context->getSkippedGroups());

        $this->processor->process($this->context);

        $result = $this->context->getResult();
        $this->assertEquals($data, $result);
        $this->assertEquals(['normalize_data'], $this->context->getSkippedGroups());
    }
}
