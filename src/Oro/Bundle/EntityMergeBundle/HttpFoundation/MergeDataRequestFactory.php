<?php

namespace Oro\Bundle\EntityMergeBundle\HttpFoundation;

use Symfony\Component\HttpFoundation\Request;

class MergeDataRequestFactory
{

    private $request;
    /**
     * @var MassActionParametersParser
     */
    private $parametersParser;
    /**
     * @var MassActionDispatcher
     */
    private $massActionDispatcher;

    public function __construct(MassActionParametersParser $parametersParser,
        MassActionDispatcher $massActionDispatcher)
    {

        $this->parametersParser = $parametersParser;
        $this->massActionDispatcher = $massActionDispatcher;
    }

    private function getDataFromRequest()
    {
        $request = $this->getRequest();
        $gridName = $request->get('gridName', 'actionName');
        $actionName = $request->get('', null);

        /**
         * @var MassActionParametersParser $parametersParser
         */
        $parametersParser = $this->get('oro_datagrid.mass_action.parameters_parser');
        $parameters = $parametersParser->parse($request);
        $requestData = array_merge($request->query->all(), $request->request->all());

        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->get('oro_datagrid.mass_action.dispatcher');

        $parameters = $massActionDispatcher->dispatch($gridName, $actionName, $parameters, $requestData);
        return $parameters;
    }

    /**
     * @return mixed
     */
    public function createMergeData()
    {

    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        if ($request instanceof Request) {
            $this->request = clone $request;
        }
    }
}
