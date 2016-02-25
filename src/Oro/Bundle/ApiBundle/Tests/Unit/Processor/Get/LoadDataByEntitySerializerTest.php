<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\Get\LoadDataByEntitySerializer;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;


class LoadDataByEntitySerializerTest extends LoadDataByEntitySerializerTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->processor = new LoadDataByEntitySerializer($this->serializer);
        $this->context    = new GetContext($this->configProvider, $this->metadataProvider);
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcess($dataFromSerializer, $expectedResult, $isThrowable = false)
    {
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
            ->willReturn($dataFromSerializer);

        if ($isThrowable) {
            $this->setExpectedException('\RuntimeException', 'The result must have one or zero items.');
        }

        $this->assertEquals([], $this->context->getSkippedGroups());

        $this->processor->process($this->context);

        if (!$isThrowable) {
            $result = $this->context->getResult();
            $this->assertEquals($expectedResult, $result);
            $this->assertEquals(['normalize_data'], $this->context->getSkippedGroups());
        }
    }

    public function processProvider()
    {
        $group1 = new Group();
        $group1->setId(12);
        $group2 = new Group();
        $group2->setId(25);
        return [
            'has ro records'     => [
                [],
                null
            ],
            'return one record'  => [
                [$group1],
                $group1
            ],
            'return two records' => [
                [$group1, $group2],
                null,
                true
            ]
        ];
    }
}
