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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class MassActionHelperTest extends TestCase
{
    private const MASS_ACTION_NAME = 'massActionName';
    private const HANDLER_SERVICE_ID = 'handlerServiceId';

    private ContainerInterface&MockObject $container;
    private MassActionHelper $massActionHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->massActionHelper = new MassActionHelper($this->container);
    }

    public function testGetHandlerWhenNoHandlerOptionExists(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('There is no handler for mass action "%s"', self::MASS_ACTION_NAME));

        $massAction = $this->creatMassAction(ActionConfiguration::create([]));

        $this->massActionHelper->getHandler($massAction);
    }

    public function testGetHandlerWhenNoHandlerServiceExists(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('Mass action handler service "%s" not exist', self::HANDLER_SERVICE_ID));

        $massAction = $this->creatMassAction(ActionConfiguration::create(['handler' => self::HANDLER_SERVICE_ID]));

        $this->container->expects($this->once())
            ->method('has')
            ->with(self::HANDLER_SERVICE_ID)
            ->willReturn(false);

        $this->massActionHelper->getHandler($massAction);
    }

    public function testGetHandlerWhenHandlerIsOfBadInterface(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $massAction = $this->creatMassAction(ActionConfiguration::create(['handler' => self::HANDLER_SERVICE_ID]));

        $this->container->expects($this->once())
            ->method('has')
            ->with(self::HANDLER_SERVICE_ID)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with(self::HANDLER_SERVICE_ID)
            ->willReturn(new \stdClass());

        $this->massActionHelper->getHandler($massAction);
    }

    public function testGetHandlerWhenHandler(): void
    {
        $massAction = $this->creatMassAction(ActionConfiguration::create(['handler' => self::HANDLER_SERVICE_ID]));

        $this->container->expects($this->once())
            ->method('has')
            ->with(self::HANDLER_SERVICE_ID)
            ->willReturn(true);

        $handler = $this->createMock(MassActionHandlerInterface::class);
        $this->container->expects($this->once())
            ->method('get')
            ->with(self::HANDLER_SERVICE_ID)
            ->willReturn($handler);

        $this->assertSame($handler, $this->massActionHelper->getHandler($massAction));
    }

    public function testGetMassActionByNameWhenNoMassActionExtension(): void
    {
        $dataGrid = $this->createMock(DatagridInterface::class);

        $extension = $this->createMock(ExtensionVisitorInterface::class);

        $this->setDatagridExtensions($dataGrid, [$extension]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No MassAction extension found for the datagrid.');

        $this->massActionHelper->getMassActionByName(self::MASS_ACTION_NAME, $dataGrid);
    }

    public function testGetMassActionByNameWhenNoMassAction(): void
    {
        $dataGrid = $this->createMock(DatagridInterface::class);

        $extension = $this->createMock(MassActionExtension::class);
        $extension->expects($this->once())
            ->method('getMassAction')
            ->with(self::MASS_ACTION_NAME, $dataGrid)
            ->willReturn(null);

        $this->setDatagridExtensions($dataGrid, [$extension]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(sprintf('Can\'t find mass action "%s"', self::MASS_ACTION_NAME));

        $this->massActionHelper->getMassActionByName(self::MASS_ACTION_NAME, $dataGrid);
    }

    public function testGetMassActionByName(): void
    {
        $dataGrid = $this->createMock(DatagridInterface::class);

        $massAction = $this->createMock(MassActionInterface::class);

        $extension = $this->createMock(MassActionExtension::class);
        $extension->expects($this->once())
            ->method('getMassAction')
            ->with(self::MASS_ACTION_NAME, $dataGrid)
            ->willReturn($massAction);

        $this->setDatagridExtensions($dataGrid, [$extension]);

        $this->assertSame($massAction, $this->massActionHelper->getMassActionByName(self::MASS_ACTION_NAME, $dataGrid));
    }

    public function testIsRequestMethodAllowed(): void
    {
        $massAction = $this->createMock(MassActionInterface::class);
        $massAction->expects($this->any())
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

    private function setDatagridExtensions(DatagridInterface&MockObject $datagrid, array $extensions): void
    {
        $acceptor = $this->createMock(Acceptor::class);
        $acceptor->expects($this->once())
            ->method('getExtensions')
            ->willReturn($extensions);

        $datagrid->expects($this->once())
            ->method('getAcceptor')
            ->willReturn($acceptor);
    }

    private function creatMassAction(ActionConfiguration $actionConfiguration): MassActionInterface
    {
        $massAction = $this->createMock(MassActionInterface::class);
        $massAction->expects($this->any())
            ->method('getName')
            ->willReturn(self::MASS_ACTION_NAME);
        $massAction->expects($this->any())
            ->method('getOptions')
            ->willReturn($actionConfiguration);

        return $massAction;
    }
}
