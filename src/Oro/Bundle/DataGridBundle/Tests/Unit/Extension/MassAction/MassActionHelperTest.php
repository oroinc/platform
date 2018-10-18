<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException;
use Oro\Bundle\DataGridBundle\Extension\Acceptor;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\ExtensionVisitorInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionExtension;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class MassActionHelperTest extends \PHPUnit\Framework\TestCase
{
    const MASS_ACTION_NAME = 'massActionName';
    const HANDLER_SERVICE_ID = 'handlerServiceId';

    /**
     * @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $container;

    /**
     * @var MassActionHelper
     */
    protected $massActionHelper;

    protected function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->massActionHelper = new MassActionHelper($this->container);
    }

    public function testGetHandlerWhenNoHandlerOptionExists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('There is no handler for mass action "%s"', self::MASS_ACTION_NAME));

        $massAction = $this->creatMassAction(ActionConfiguration::create([]));

        $this->massActionHelper->getHandler($massAction);
    }

    public function testGetHandlerWhenNoHandlerServiceExists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('Mass action handler service "%s" not exist', self::HANDLER_SERVICE_ID));

        $massAction = $this->creatMassAction(ActionConfiguration::create(['handler' => self::HANDLER_SERVICE_ID]));

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with(self::HANDLER_SERVICE_ID)
            ->willReturn(false);

        $this->massActionHelper->getHandler($massAction);
    }

    public function testGetHandlerWhenHandlerIsOfBadInterface()
    {
        $this->expectException(UnexpectedTypeException::class);

        $massAction = $this->creatMassAction(ActionConfiguration::create(['handler' => self::HANDLER_SERVICE_ID]));

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with(self::HANDLER_SERVICE_ID)
            ->willReturn(true);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(self::HANDLER_SERVICE_ID)
            ->willReturn(new \stdClass());

        $this->massActionHelper->getHandler($massAction);
    }

    public function testGetHandlerWhenHandler()
    {
        $massAction = $this->creatMassAction(ActionConfiguration::create(['handler' => self::HANDLER_SERVICE_ID]));

        $this->container
            ->expects($this->once())
            ->method('has')
            ->with(self::HANDLER_SERVICE_ID)
            ->willReturn(true);

        $handler = $this->createMock(MassActionHandlerInterface::class);
        $this->container
            ->expects($this->once())
            ->method('get')
            ->with(self::HANDLER_SERVICE_ID)
            ->willReturn($handler);

        $this->assertSame($handler, $this->massActionHelper->getHandler($massAction));
    }

    public function testGetMassActionByNameWhenNoMassActionExtension()
    {
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $dataGrid */
        $dataGrid = $this->createMock(DatagridInterface::class);

        /** @var ExtensionVisitorInterface|\PHPUnit\Framework\MockObject\MockObject $extension */
        $extension = $this->createMock(ExtensionVisitorInterface::class);

        $this->setDatagridExtensions($dataGrid, [$extension]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No MassAction extension found for the datagrid.');

        $this->massActionHelper->getMassActionByName(self::MASS_ACTION_NAME, $dataGrid);
    }

    public function testGetMassActionByNameWhenNoMassAction()
    {
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $dataGrid */
        $dataGrid = $this->createMock(DatagridInterface::class);

        /** @var MassActionExtension|\PHPUnit\Framework\MockObject\MockObject $extension */
        $extension = $this->createMock(MassActionExtension::class);
        $extension
            ->expects($this->once())
            ->method('getMassAction')
            ->with(self::MASS_ACTION_NAME, $dataGrid)
            ->willReturn(null);

        $this->setDatagridExtensions($dataGrid, [$extension]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('Can\'t find mass action "%s"', self::MASS_ACTION_NAME));

        $this->massActionHelper->getMassActionByName(self::MASS_ACTION_NAME, $dataGrid);
    }

    public function testGetMassActionByName()
    {
        /** @var DatagridInterface|\PHPUnit\Framework\MockObject\MockObject $dataGrid */
        $dataGrid = $this->createMock(DatagridInterface::class);

        /** @var MassActionInterface $massAction */
        $massAction = $this->createMock(MassActionInterface::class);

        /** @var MassActionExtension|\PHPUnit\Framework\MockObject\MockObject $extension */
        $extension = $this->createMock(MassActionExtension::class);
        $extension
            ->expects($this->once())
            ->method('getMassAction')
            ->with(self::MASS_ACTION_NAME, $dataGrid)
            ->willReturn($massAction);

        $this->setDatagridExtensions($dataGrid, [$extension]);

        $this->assertSame($massAction, $this->massActionHelper->getMassActionByName(self::MASS_ACTION_NAME, $dataGrid));
    }

    public function testIsRequestMethodAllowed()
    {
        /** @var MassActionInterface|\PHPUnit\Framework\MockObject\MockObject $massAction */
        $massAction = $this->createMock(MassActionInterface::class);
        $massAction
            ->expects($this->any())
            ->method('getOptions')
            ->willReturn(
                ActionConfiguration::create(
                    [
                        MassActionExtension::ALLOWED_REQUEST_TYPES => [Request::METHOD_GET, Request::METHOD_POST]
                    ]
                )
            );

        $this->assertTrue($this->massActionHelper->isRequestMethodAllowed($massAction, Request::METHOD_GET));
        $this->assertFalse($this->massActionHelper->isRequestMethodAllowed($massAction, Request::METHOD_DELETE));
    }

    /**
     * @param $datagrid
     * @param array $extensions
     */
    private function setDatagridExtensions($datagrid, array $extensions)
    {
        $acceptor = $this->createMock(Acceptor::class);

        $acceptor
            ->expects($this->once())
            ->method('getExtensions')
            ->willReturn($extensions);

        /** @var Acceptor|\PHPUnit\Framework\MockObject\MockObject $datagrid */
        $datagrid
            ->expects($this->once())
            ->method('getAcceptor')
            ->willReturn($acceptor);
    }

    /**
     * @param ActionConfiguration $actionConfiguration
     * @return MassActionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function creatMassAction(ActionConfiguration $actionConfiguration)
    {
        /** @var MassActionInterface|\PHPUnit\Framework\MockObject\MockObject $massAction **/
        $massAction = $this->createMock(MassActionInterface::class);

        $massAction
            ->expects($this->any())
            ->method('getName')
            ->willReturn(self::MASS_ACTION_NAME);

        $massAction
            ->expects($this->any())
            ->method('getOptions')
            ->willReturn($actionConfiguration);

        return $massAction;
    }
}
