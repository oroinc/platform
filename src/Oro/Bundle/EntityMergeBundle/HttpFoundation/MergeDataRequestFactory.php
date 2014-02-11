<?php

namespace Oro\Bundle\EntityMergeBundle\HttpFoundation;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionParametersParser;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\EntityDataFactory;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory;
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

    /**
     * @var @var EntityData $requestData
     */
    private $requestData;

    private $entityDataFactory;

    public function __construct(
        MassActionParametersParser $parametersParser,
        MassActionDispatcher $massActionDispatcher,
        EntityDataFactory $entityDataFactory
    ) {
        $this->parametersParser = $parametersParser;
        $this->massActionDispatcher = $massActionDispatcher;
        $this->entityDataFactory = $entityDataFactory;
    }

    /**
     * @return EntityData
     */
    private function getDataFromRequest()
    {
        if ($this->requestData !== null) {
            return $this->requestData;
        }

        $request = $this->getRequest();

        $gridName = $request->get('gridName', null);
        $actionName = $request->get('actionName', null);

        $parameters = $this->parametersParser->parse($request);
        $requestData = array_merge($request->query->all(), $request->request->all());
        $handlerResult = $this->massActionDispatcher->dispatch($gridName, $actionName, $parameters, $requestData);

        $options = $handlerResult['options'];
        $entities = $handlerResult['entities'];
        $data = $this->entityDataFactory->createEntityData($options['entity_name'], $entities);

        $this->requestData = $data;

        return $this->requestData;
    }

    /**
     * @return EntityData
     */
    public function createMergeData()
    {
        return $this->getDataFromRequest();
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
