<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\EntityBundle\Api\Processor\LoadEntityStructureData;
use Oro\Bundle\EntityBundle\Model\EntityStructure;
use Oro\Bundle\EntityBundle\Provider\EntityStructureDataProvider;

class LoadEntityStructureDataTest extends \PHPUnit_Framework_TestCase
{
    /** @var LoadEntityStructureData */
    protected $processor;

    /** @var EntityStructureDataProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->provider = $this->createMock(EntityStructureDataProvider::class);
        $this->processor = new LoadEntityStructureData($this->provider);
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
     * @dataProvider unsupportedActionDataProvider
     */
    public function testProcessWithUnsupportedActions($action)
    {
        $context = $this->createMock(GetContext::class);
        $context->expects($this->once())
            ->method('getAction')
            ->willReturn($action);

        $this->expectException(ActionNotAllowedException::class);
        $this->expectExceptionMessage('The action is not allowed.');

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
            ApiActions::GET => [ApiActions::GET],
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
