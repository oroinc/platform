<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DTO\SelectedItems;
use Oro\Bundle\FilterBundle\Grid\Extension\OrmFilterExtension;
use Symfony\Component\HttpFoundation\Request;

class MassActionDispatcher
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var MassActionHelper
     */
    protected $massActionHelper;

    /**
     * @var MassActionParametersParser
     */
    protected $massActionParametersParser;

    /**
     * @var IterableResultFactoryRegistry
     */
    protected $iterableResultFactoryRegistry;

    /**
     * @param Manager $manager
     * @param MassActionHelper $massActionHelper
     * @param MassActionParametersParser $massActionParametersParser
     * @param IterableResultFactoryRegistry $iterableResultFactoryRegistry
     */
    public function __construct(
        Manager $manager,
        MassActionHelper $massActionHelper,
        MassActionParametersParser $massActionParametersParser,
        IterableResultFactoryRegistry $iterableResultFactoryRegistry
    ) {
        $this->manager = $manager;
        $this->massActionHelper = $massActionHelper;
        $this->massActionParametersParser = $massActionParametersParser;
        $this->iterableResultFactoryRegistry = $iterableResultFactoryRegistry;
    }

    /**
     * @param string $datagridName
     * @param string $actionName
     * @param Request $request
     *
     * @return MassActionResponseInterface
     */
    public function dispatchByRequest($datagridName, $actionName, Request $request): MassActionResponseInterface
    {
        $parameters = $this->massActionParametersParser->parse($request);

        $requestData = array_merge($request->query->all(), $request->request->all());

        return $this->dispatch($datagridName, $actionName, $parameters, $requestData);
    }

    /**
     * @param string $datagridName
     * @param string $actionName
     * @param array  $parameters
     * @param array  $data
     *
     * @throws LogicException
     *
     * @return MassActionResponseInterface
     */
    public function dispatch(
        $datagridName,
        $actionName,
        array $parameters,
        array $data = []
    ): MassActionResponseInterface {
        $selectedItems = SelectedItems::createFromParameters($parameters);

        if ($selectedItems->isEmpty()) {
            throw new LogicException(sprintf('There is nothing to do in mass action "%s"', $actionName));
        }

        $filters = [];
        if (isset($parameters['filters'])) {
            $filters = $parameters['filters'];
        }

        // create datagrid
        $datagrid = $this->manager->getDatagridByRequestParams($datagridName);

        // set filter data
        $datagrid->getParameters()->mergeKey(OrmFilterExtension::FILTER_ROOT_PARAM, $filters);

        // create mediator
        $massAction = $this->massActionHelper->getMassActionByName($actionName, $datagrid);

        // perform mass action
        $handler = $this->massActionHelper->getHandler($massAction);

        // Call service
        $resultIterator = $this->iterableResultFactoryRegistry->createIterableResult(
            $datagrid->getAcceptedDatasource(),
            $massAction->getOptions(),
            $datagrid->getConfig(),
            $selectedItems
        );

        $handlerArgs = new MassActionHandlerArgs($massAction, $datagrid, $resultIterator, $data);

        return $handler->handle($handlerArgs);
    }
}
