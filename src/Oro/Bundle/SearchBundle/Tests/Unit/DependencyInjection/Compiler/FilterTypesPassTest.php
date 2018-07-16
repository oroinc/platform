<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SearchBundle\DependencyInjection\Compiler\FilterTypesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class FilterTypesPassTest extends \PHPUnit\Framework\TestCase
{
    const TEST_TAG_ATTRIBUTE_TYPE = 'TEST_TAG_ATTRIBUTE_TYPE';
    const TEST_SERVICE_ID         = 'TEST_SERVICE_ID';

    /**
     * @var FilterTypesPass
     */
    protected $filterTypePass;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder
     */
    protected $containerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Definition
     */
    protected $definitionMock;

    public function setUp()
    {
        $this->filterTypePass = new FilterTypesPass();

        $this->definitionMock = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->containerMock = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testProcessSearch()
    {
        $this->containerMock
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(FilterTypesPass::TAG_NAME)
            ->willReturn(
                [
                    self::TEST_SERVICE_ID => [
                        [
                            'datasource' => 'search',
                            'type'       => self::TEST_TAG_ATTRIBUTE_TYPE,
                        ]
                    ]
                ]
            );

        $this->containerMock
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(self::TEST_SERVICE_ID)
            ->willReturn(true);

        $this->containerMock
            ->expects($this->at(0))
            ->method('getDefinition')
            ->with(FilterTypesPass::FILTER_EXTENSION_ID)
            ->willReturn($this->definitionMock);

        $definitionMock2 = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $definitionMock2
            ->expects($this->once())
            ->method('setPublic')
            ->with(false);

        $this->containerMock
            ->expects($this->at(3))
            ->method('getDefinition')
            ->with(self::TEST_SERVICE_ID)
            ->willReturn($definitionMock2);

        $this->definitionMock
            ->expects($this->once())
            ->method('addMethodCall');

        $this->filterTypePass->process($this->containerMock);
    }

    public function testProcessNonSearch()
    {
        $this->containerMock
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(FilterTypesPass::TAG_NAME)
            ->willReturn(
                [
                    self::TEST_SERVICE_ID => [
                        [
                            'datasource' => 'orm',
                            'type'       => self::TEST_TAG_ATTRIBUTE_TYPE,
                        ]
                    ]
                ]
            );

        $this->containerMock
            ->expects($this->never())
            ->method('hasDefinition');

        $this->containerMock
            ->expects($this->at(0))
            ->method('getDefinition')
            ->with(FilterTypesPass::FILTER_EXTENSION_ID)
            ->willReturn($this->definitionMock);

        $this->definitionMock
            ->expects($this->never())
            ->method('addMethodCall');

        $this->filterTypePass->process($this->containerMock);
    }
}
