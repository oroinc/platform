<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\EntityBundle\Api\Processor\EntityStructureLoadDataProcessor;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider;

class EntityStructureLoadDataProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityStructureLoadDataProcessor */
    protected $processor;

    /** @var EntityStructureDataProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->provider = $this->createMock(EntityStructureDataProvider::class);
        $this->processor = new EntityStructureLoadDataProcessor($this->provider);
    }

    /**
     * @param string $id
     * @param bool $expected
     *
     * @dataProvider processGetDataProvider
     */
    public function testProcessGet($id, $expected)
    {
        $context = $this->createMock(GetContext::class);
        $context->expects($this->once())
            ->method('getAction')
            ->willReturn(ApiActions::GET);
        $context->expects($this->once())
            ->method('getId')
            ->willReturn($id);
        $this->provider->expects($this->once())
            ->method('getData')
            ->willReturn([
                (new EntityStructure())->setClassName(\stdClass::class),
                (new EntityStructure())->setClassName('Class\Test'),
            ]);
        $context->expects($this->exactly((int)$expected))
            ->method('setResult')
            ->with($this->isInstanceOf(EntityStructure::class));

        $this->processor->process($context);
    }

    /**
     * @return array
     */
    public function processGetDataProvider()
    {
        return [
            'positive simple' => ['id' => \stdClass::class, 'expected' => true],
            'positive with namespace' => ['id' => 'Class_Test', 'expected' => true],
            'negative simple' => ['id' => 'OtherClass', 'expected' => false],
            'negative with namespace' => ['id' => 'Class_Test_Other', 'expected' => false],
        ];
    }

    public function testProcessGetList()
    {
        $context = $this->createMock(GetContext::class);
        $context->expects($this->once())
            ->method('getAction')
            ->willReturn(ApiActions::GET_LIST);
        $data = [
            (new EntityStructure())->setClassName(\stdClass::class),
            (new EntityStructure())->setClassName('Class\Test'),
        ];
        $this->provider->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $context->expects($this->once())
            ->method('setResult')
            ->with($data);

        $this->processor->process($context);
    }

    /**
     * @param string $action
     *
     * @dataProvider processGetDataProvider
     */
    public function testProcessWithUnsupportedActions($action)
    {
        $context = $this->createMock(GetContext::class);
        $context->expects($this->once())
            ->method('getAction')
            ->willReturn($action);
        $this->provider->expects($this->never())
            ->method('getData');
        $context->expects($this->never())
            ->method('setResult');

        $this->processor->process($context);
    }

    /**
     * @return array
     */
    public function unsupportedActionDataProvider()
    {
        return [
            ApiActions::UPDATE => [ApiActions::UPDATE],
            ApiActions::CREATE => [ApiActions::CREATE],
            ApiActions::DELETE => [ApiActions::DELETE],
            ApiActions::DELETE_LIST => [ApiActions::DELETE_LIST],
            ApiActions::GET_SUBRESOURCE => [ApiActions::GET_SUBRESOURCE],
            ApiActions::GET_RELATIONSHIP => [ApiActions::GET_RELATIONSHIP],
            ApiActions::UPDATE_RELATIONSHIP => [ApiActions::UPDATE_RELATIONSHIP],
            ApiActions::ADD_RELATIONSHIP => [ApiActions::ADD_RELATIONSHIP],
            ApiActions::DELETE_RELATIONSHIP => [ApiActions::DELETE_RELATIONSHIP],
        ];
    }
}
