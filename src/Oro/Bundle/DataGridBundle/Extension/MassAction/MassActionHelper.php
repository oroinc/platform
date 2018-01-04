<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException;
use Oro\Bundle\DataGridBundle\Extension\ExtensionVisitorInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This class provides useful methods for mass actions.
 */
class MassActionHelper
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param MassActionInterface $massAction
     * @return MassActionHandlerInterface
     * @throws UnexpectedTypeException
     * @throws LogicException
     */
    public function getHandler(MassActionInterface $massAction): MassActionHandlerInterface
    {
        $handlerServiceId = $massAction->getOptions()->offsetGetOr('handler');

        if (!$handlerServiceId) {
            throw new LogicException(sprintf('There is no handler for mass action "%s"', $massAction->getName()));
        }

        if (!$this->container->has($handlerServiceId)) {
            throw new LogicException(sprintf('Mass action handler service "%s" not exist', $handlerServiceId));
        }

        $handler = $this->container->get($handlerServiceId);
        if (!$handler instanceof MassActionHandlerInterface) {
            throw new UnexpectedTypeException($handler, 'MassActionHandlerInterface');
        }

        return $handler;
    }

    /**
     * @param MassActionInterface $massAction
     * @param string $httpMethod
     * @return bool
     */
    public function isRequestMethodAllowed(MassActionInterface $massAction, string $httpMethod): bool
    {
        $configuredMethods = $massAction->getOptions()->offsetGetOr(MassActionExtension::ALLOWED_REQUEST_TYPES, []);

        return in_array($httpMethod, $configuredMethods, true);
    }

    /**
     * @param string $massActionName
     * @param DatagridInterface $dataGrid
     * @return MassActionInterface
     */
    public function getMassActionByName($massActionName, DatagridInterface $dataGrid): MassActionInterface
    {
        $massAction = null;
        $extensions = array_filter(
            $dataGrid->getAcceptor()->getExtensions(),
            function (ExtensionVisitorInterface $extension) {
                return $extension instanceof MassActionExtension;
            }
        );

        /** @var MassActionExtension|bool $extension */
        $extension = reset($extensions);
        if ($extension === false) {
            throw new LogicException('No MassAction extension found for the datagrid.');
        }

        $massAction = $extension->getMassAction($massActionName, $dataGrid);

        if (!$massAction) {
            throw new LogicException(sprintf('Can\'t find mass action "%s"', $massActionName));
        }

        return $massAction;
    }
}
