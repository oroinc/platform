<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Batch\Model\BatchSummary;
use Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultContext;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ParameterBagInterface;

/**
 * The context for the "batch_update_item" action.
 */
class BatchUpdateItemContext extends ByStepNormalizeResultContext
{
    /** FQCN of an entity */
    private const CLASS_NAME = 'class';

    /** the name of the target action */
    private const TARGET_ACTION = 'targetAction';

    private mixed $id = null;
    private ?BatchSummary $summary = null;
    /** @var string[] */
    private array $supportedEntityClasses = [];
    private ?array $requestData = null;
    private ?ActionProcessorInterface $targetProcessor = null;
    private ?Context $targetContext = null;
    private ParameterBagInterface $sharedData;

    /**
     * Gets FQCN of an entity.
     */
    public function getClassName(): ?string
    {
        return $this->get(self::CLASS_NAME);
    }

    /**
     * Sets FQCN of an entity.
     */
    public function setClassName(?string $className): void
    {
        if (null === $className) {
            $this->remove(self::CLASS_NAME);
        } else {
            $this->set(self::CLASS_NAME, $className);
        }
    }

    /**
     * Gets an identifier of an entity.
     */
    public function getId(): mixed
    {
        return $this->id;
    }

    /**
     * Sets an identifier of an entity.
     */
    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    /**
     * Gets the summary statistics of this batch operation.
     */
    public function getSummary(): ?BatchSummary
    {
        return $this->summary;
    }

    /**
     * Sets the summary statistics of this batch operation.
     */
    public function setSummary(?BatchSummary $summary): void
    {
        $this->summary = $summary;
    }

    /**
     * Gets entity classes supported by this batch operation.
     *
     * @return string[] The list of supported entity classes.
     *                  or empty array if any entities can be processed by this batch operation.
     */
    public function getSupportedEntityClasses(): array
    {
        return $this->supportedEntityClasses;
    }

    /**
     * Sets entity classes supported by this batch operation.
     *
     * @param string[] $supportedEntityClasses
     */
    public function setSupportedEntityClasses(array $supportedEntityClasses): void
    {
        $this->supportedEntityClasses = $supportedEntityClasses;
    }

    /**
     * Gets the request data.
     */
    public function getRequestData(): ?array
    {
        return $this->requestData;
    }

    /**
     * Sets the request data.
     */
    public function setRequestData(?array $requestData): void
    {
        $this->requestData = $requestData;
    }

    /**
     * Gets the name of the target action.
     */
    public function getTargetAction(): ?string
    {
        return $this->get(self::TARGET_ACTION);
    }

    /**
     * Sets the name of the target action.
     */
    public function setTargetAction(?string $action): void
    {
        if (null === $action) {
            $this->remove(self::TARGET_ACTION);
        } else {
            $this->set(self::TARGET_ACTION, $action);
        }
    }

    /**
     * Gets the processor responsible to process the request data.
     */
    public function getTargetProcessor(): ?ActionProcessorInterface
    {
        return $this->targetProcessor;
    }

    /**
     * Sets the processor responsible to process the request data.
     */
    public function setTargetProcessor(?ActionProcessorInterface $processor): void
    {
        $this->targetProcessor = $processor;
    }

    /**
     * Gets the context which should be used when processing the request data.
     */
    public function getTargetContext(): ?Context
    {
        return $this->targetContext;
    }

    /**
     * Sets the context which should be used when processing the request data.
     */
    public function setTargetContext(?Context $context): void
    {
        $this->targetContext = $context;
    }

    /**
     * Gets an object that is used to share data between a primary action
     * and actions that are executed as part of this action.
     * Also, this object can be used to share data between different kind of child actions.
     */
    public function getSharedData(): ParameterBagInterface
    {
        return $this->sharedData;
    }

    /**
     * Sets an object that is used to share data between a primary action
     * and actions that are executed as part of this action.
     * Also, this object can be used to share data between different kind of child actions.
     */
    public function setSharedData(ParameterBagInterface $sharedData): void
    {
        $this->sharedData = $sharedData;
    }
}
