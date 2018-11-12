<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadEntityByEntitySerializer;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorOrmRelatedTestCase;
use Oro\Component\EntitySerializer\EntitySerializer;

class LoadEntityByEntitySerializerTest extends GetProcessorOrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntitySerializer */
    private $serializer;

    /** @var LoadEntityByEntitySerializer */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->serializer = $this->createMock(EntitySerializer::class);

        $this->processor = new LoadEntityByEntitySerializer($this->serializer);
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

    /**
     * @dataProvider processProvider
     */
    public function testProcess($dataFromSerializer, $expectedResult, $isThrowable = false)
    {
        $entityClass = Group::class;

        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');

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
            ->willReturn($dataFromSerializer);

        if ($isThrowable) {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('The result must have one or zero items.');
        }

        $this->context->setClassName($entityClass);
        $this->context->setQuery($query);
        $this->processor->process($this->context);

        if (!$isThrowable) {
            $result = $this->context->getResult();
            self::assertEquals($expectedResult, $result);
            self::assertEquals(['normalize_data'], $this->context->getSkippedGroups());
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
