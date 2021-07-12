<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ParameterBagInterface;

/**
 * The context for the handler to flush all ORM entities that are changed by API to the database.
 */
class FlushDataHandlerContext
{
    private array $entityContexts;
    private ParameterBagInterface $sharedData;

    /**
     * @param FormContext[]         $entityContexts
     * @param ParameterBagInterface $sharedData
     */
    public function __construct(array $entityContexts, ParameterBagInterface $sharedData)
    {
        $this->entityContexts = $entityContexts;
        $this->sharedData = $sharedData;
    }

    /**
     * @return FormContext[]
     */
    public function getEntityContexts(): array
    {
        return $this->entityContexts;
    }

    /**
     * Gets an object that is used to share data between the handler and API action that call the handler.
     */
    public function getSharedData(): ParameterBagInterface
    {
        return $this->sharedData;
    }
}
