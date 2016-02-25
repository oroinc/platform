<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get;

use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Config\Config;

class LoadDataByEntitySerializerTestCase extends OrmRelatedTestCase
{
    protected $context;

    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $serializer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->serializer = $this->getMockBuilder('Oro\Component\EntitySerializer\EntitySerializer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
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
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn(new Config());
        $this->assertFalse($this->context->hasResult());
        $this->assertFalse($this->context->hasConfig());
        $entityClass = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group';
        $this->context->setClassName($entityClass);
        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');
        $this->context->setQuery($query);

        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasResult());
    }
}
