<?php

namespace Oro\Bundle\EntityMergeBundle\HttpFoundation;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionParametersParser;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
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
     * @var MetadataFactory $metadataFactory
     */
    private $metadataFactory;

    /**
     * @var @var EntityData $requestData
     */
    private $requestData;

    public function __construct(
        MassActionParametersParser $parametersParser,
        MassActionDispatcher $massActionDispatcher,
        MetadataFactory $metadataFactory
    ) {
        $this->parametersParser = $parametersParser;
        $this->massActionDispatcher = $massActionDispatcher;
        $this->metadataFactory = $metadataFactory;
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

        $entityMetadata = $this->metadataFactory->createMergeMetadata($options['entity_name']);

        $data = new EntityData($entityMetadata);
        $data->setEntities($entities);
        foreach ($entityMetadata->getFieldsMetadata() as $fieldMetadata) {
            $data->addNewField($fieldMetadata);
        }

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
