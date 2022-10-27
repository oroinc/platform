<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;

class ManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigurationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var Builder|\PHPUnit\Framework\MockObject\MockObject */
    private $builder;

    /** @var RequestParameterBagFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $parametersFactory;

    /** @var NameStrategyInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $nameStrategy;

    /** @var Manager */
    private $manager;

    protected function setUp(): void
    {
        $this->configurationProvider = $this->createMock(ConfigurationProviderInterface::class);
        $this->builder = $this->createMock(Builder::class);
        $this->parametersFactory = $this->createMock(RequestParameterBagFactory::class);
        $this->nameStrategy = $this->createMock(NameStrategyInterface::class);

        $this->nameStrategy->expects($this->any())
            ->method('getGridUniqueName')
            ->willReturnCallback(function ($name) {
                return $name;
            });

        $this->manager = new Manager(
            $this->configurationProvider,
            $this->builder,
            $this->parametersFactory,
            $this->nameStrategy
        );
    }

    public function testGetDatagridByRequestParamsWorksWithoutScope()
    {
        $datagridName = 'test_grid';
        $additionalParameters = ['foo' => 'bar'];

        $parameters = $this->createMock(ParameterBag::class);
        $parameters->expects($this->once())
            ->method('add')
            ->with($additionalParameters);

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridScope')
            ->with($datagridName)
            ->willReturn(null);

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridName')
            ->with($datagridName)
            ->willReturn($datagridName);

        $this->parametersFactory->expects($this->once())
            ->method('createParameters')
            ->with($datagridName)
            ->willReturn($parameters);

        $configuration = $this->createMock(DatagridConfiguration::class);

        $datagrid = $this->createMock(DatagridInterface::class);

        $this->configurationProvider->expects($this->exactly(2))
            ->method('getConfiguration')
            ->with($datagridName)
            ->willReturn($configuration);

        $configuration->expects($this->once())
            ->method('offsetGetOr')
            ->with('scope')
            ->willReturn(null);

        $configuration->expects($this->never())
            ->method('offsetSet');

        $this->builder->expects($this->once())
            ->method('build')
            ->with($configuration, $parameters, $additionalParameters)
            ->willReturn($datagrid);

        $this->assertEquals(
            $datagrid,
            $this->manager->getDatagridByRequestParams($datagridName, $additionalParameters)
        );
    }

    public function testGetDatagridByRequestParamsWorksWithScope()
    {
        $gridFullName = 'test_grid:test_scope';
        $gridName = 'test_grid';
        $gridScope = 'test_scope';
        $additionalParameters = ['foo' => 'bar'];

        $parameters = $this->createMock(ParameterBag::class);
        $parameters->expects($this->once())
            ->method('add')
            ->with($additionalParameters);
        $parameters->expects($this->once())
            ->method('all')
            ->willReturn([]);

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridScope')
            ->with($gridFullName)
            ->willReturn($gridScope);

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridName')
            ->with($gridFullName)
            ->willReturn($gridName);

        $this->parametersFactory->expects($this->exactly(2))
            ->method('createParameters')
            ->willReturnMap([
                [$gridFullName, $parameters],
                [$gridName, $parameters]
            ]);

        $configuration = $this->createMock(DatagridConfiguration::class);

        $configuration->expects($this->never())
            ->method('offsetGetOr');

        $configuration->expects($this->once())
            ->method('offsetSet')
            ->with('scope', $gridScope);

        $datagrid = $this->createMock(DatagridInterface::class);

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->with($gridName)
            ->willReturn($configuration);

        $this->builder->expects($this->once())
            ->method('build')
            ->with($configuration, $parameters, $additionalParameters)
            ->willReturn($datagrid);

        $this->assertEquals(
            $datagrid,
            $this->manager->getDatagridByRequestParams($gridFullName, $additionalParameters)
        );
    }

    public function testGetDatagridWithoutScope()
    {
        $datagridName = 'test_grid';
        $parameters = $this->createMock(ParameterBag::class);

        $this->nameStrategy->expects($this->once())
            ->method('parseGridScope')
            ->with($datagridName)
            ->willReturn(null);

        $this->nameStrategy->expects($this->once())
            ->method('parseGridName')
            ->with($datagridName)
            ->willReturn($datagridName);

        $configuration = $this->createMock(DatagridConfiguration::class);

        $configuration->expects($this->never())
            ->method($this->anything());

        $datagrid = $this->createMock(DatagridInterface::class);

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->with($datagridName)
            ->willReturn($configuration);

        $this->builder->expects($this->once())
            ->method('build')
            ->with($configuration, $parameters)
            ->willReturn($datagrid);

        $this->assertEquals(
            $datagrid,
            $this->manager->getDatagrid($datagridName, $parameters)
        );
    }

    public function testGetDatagridWithScope()
    {
        $gridFullName = 'test_grid:test_scope';
        $gridName = 'test_grid';
        $gridScope = 'test_scope';
        $parameters = $this->createMock(ParameterBag::class);

        $this->nameStrategy->expects($this->once())
            ->method('parseGridScope')
            ->with($gridFullName)
            ->willReturn($gridScope);

        $this->nameStrategy->expects($this->once())
            ->method('parseGridName')
            ->with($gridFullName)
            ->willReturn($gridName);

        $configuration = $this->createMock(DatagridConfiguration::class);

        $configuration->expects($this->never())
            ->method('offsetGetOr');

        $configuration->expects($this->once())
            ->method('offsetSet')
            ->with('scope', $gridScope);

        $datagrid = $this->createMock(DatagridInterface::class);

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->with($gridName)
            ->willReturn($configuration);

        $this->builder->expects($this->once())
            ->method('build')
            ->with($configuration, $parameters)
            ->willReturn($datagrid);

        $this->assertEquals(
            $datagrid,
            $this->manager->getDatagrid($gridFullName, $parameters)
        );
    }

    public function testGetDatagridWithDefaultParameters()
    {
        $datagridName = 'test_grid';

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridScope')
            ->with($datagridName)
            ->willReturn(null);

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridName')
            ->with($datagridName)
            ->willReturn($datagridName);

        $configuration = $this->createMock(DatagridConfiguration::class);

        $datagrid = $this->createMock(DatagridInterface::class);

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->with($datagridName)
            ->willReturn($configuration);

        $this->builder->expects($this->once())
            ->method('build')
            ->with(
                $configuration,
                $this->callback(
                    function ($parameters) {
                        $this->assertInstanceOf(ParameterBag::class, $parameters);
                        $this->assertEquals([], $parameters->all());

                        return true;
                    }
                )
            )
            ->willReturn($datagrid);

        $this->assertEquals(
            $datagrid,
            $this->manager->getDatagrid($datagridName)
        );
    }

    public function testGetDatagridWithArrayParameters()
    {
        $datagridName = 'test_grid';
        $parameters = ['foo' => 'bar'];

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridScope')
            ->with($datagridName)
            ->willReturn(null);

        $this->nameStrategy->expects($this->atLeastOnce())
            ->method('parseGridName')
            ->with($datagridName)
            ->willReturn($datagridName);

        $configuration = $this->createMock(DatagridConfiguration::class);

        $datagrid = $this->createMock(DatagridInterface::class);

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->with($datagridName)
            ->willReturn($configuration);

        $this->builder->expects($this->once())
            ->method('build')
            ->with(
                $configuration,
                $this->callback(
                    function ($value) use ($parameters) {
                        $this->assertInstanceOf(ParameterBag::class, $value);
                        $this->assertEquals($parameters, $value->all());

                        return true;
                    }
                )
            )
            ->willReturn($datagrid);

        $this->assertEquals(
            $datagrid,
            $this->manager->getDatagrid($datagridName, $parameters)
        );
    }

    public function testGetDatagridThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('$parameters must be an array or instance of ParameterBag.');

        $datagridName = 'test_grid';
        $parameters = new \stdClass();

        $this->manager->getDatagrid($datagridName, $parameters);
    }
}
