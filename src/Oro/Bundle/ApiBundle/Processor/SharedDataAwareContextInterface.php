<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ContextInterface as ComponentContextInterface;
use Oro\Component\ChainProcessor\ParameterBagInterface;

/**
 * Represents an execution context for API processors that can share date between a primary action
 * and actions that are executed as part of this primary action.
 */
interface SharedDataAwareContextInterface extends ComponentContextInterface
{
    /**
     * Gets the current request type.
     * A request can belong to several types, e.g. "rest" and "json_api".
     */
    public function getRequestType(): RequestType;

    /**
     * Gets API version.
     */
    public function getVersion(): string;

    /**
     * Sets API version.
     */
    public function setVersion(string $version): void;

    /**
     * Gets an object that is used to share data between a primary action
     * and actions that are executed as part of this action.
     * Also, this object can be used to share data between different kind of child actions.
     *
     * When this action is executed as a part of a batch operation and shared data contain
     * a parameter named "payload", the value of this parameter will be stored in MQ job data.
     * The payload data must be an array with string keys, allowed values are scalars, arrays or nulls.
     * It allows to share data between processors that are executed by MQ consumer to process a batch API operation
     * and a processors that initialize this batch API operation.
     * The merge rules for the payload data added by different batch operation chunks
     * are defined in {@see \Oro\Bundle\ApiBundle\Batch\Model\BatchAffectedEntitiesMerger::mergePayloadValue()}.
     */
    public function getSharedData(): ParameterBagInterface;

    /**
     * Sets an object that is used to share data between a primary action
     * and actions that are executed as part of this action.
     * Also, this object can be used to share data between different kind of child actions.
     */
    public function setSharedData(ParameterBagInterface $sharedData): void;
}
