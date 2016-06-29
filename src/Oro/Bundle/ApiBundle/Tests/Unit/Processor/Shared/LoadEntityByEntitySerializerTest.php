<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadEntityByEntitySerializer;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorOrmRelatedTestCase;

class LoadEntityByEntitySerializerTest extends GetProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $serializer;

    /** @var LoadEntityByEntitySerializer */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->serializer = $this->getMockBuilder('Oro\Component\EntitySerializer\EntitySerializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadEntityByEntitySerializer($this->serializer);
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

    /**
     * @dataProvider processProvider
     */
    public function testProcess($dataFromSerializer, $expectedResult, $isThrowable = false)
    {
        $entityClass = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group';

        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');

        $config = new Config();
        $config->setDefinition(new EntityDefinitionConfig());
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($query)
            ->willReturn($dataFromSerializer);

        if ($isThrowable) {
            $this->setExpectedException(
                '\Oro\Bundle\ApiBundle\Exception\RuntimeException',
                'The result must have one or zero items.'
            );
        }

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
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
