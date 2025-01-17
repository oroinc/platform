<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Component\ChainProcessor\ParameterBagInterface;

/**
 * The base execution context for processors for "customize_loaded_data" and "customize_form_data" actions.
 */
abstract class CustomizeDataContext extends ApiContext implements SharedDataAwareContextInterface
{
    /** FQCN of a root entity */
    private const ROOT_CLASS_NAME = 'rootClass';

    /** a path from a root entity to a customizing entity */
    private const PROPERTY_PATH = 'propertyPath';

    /** FQCN of a customizing entity */
    private const CLASS_NAME = 'class';

    /** the name of the action which causes this action, e.g. "create" or "update" */
    private const PARENT_ACTION = 'parentAction';

    private ?EntityDefinitionConfig $rootConfig = null;
    private ?EntityDefinitionConfig $config = null;
    private ?ParameterBagInterface $sharedData = null;

    #[\Override]
    protected function initialize(): void
    {
        parent::initialize();
        $this->set(self::PARENT_ACTION, '');
    }

    /**
     * Gets FQCN of a root entity.
     */
    public function getRootClassName(): ?string
    {
        return $this->get(self::ROOT_CLASS_NAME);
    }

    /**
     * Sets FQCN of a root entity.
     */
    public function setRootClassName(string $className): void
    {
        $this->set(self::ROOT_CLASS_NAME, $className);
    }

    /**
     * Gets a path from a root entity to a customizing entity.
     */
    public function getPropertyPath(): ?string
    {
        return $this->get(self::PROPERTY_PATH);
    }

    /**
     * Sets a path from a root entity to a customizing entity.
     */
    public function setPropertyPath(string $propertyPath): void
    {
        $this->set(self::PROPERTY_PATH, $propertyPath);
    }

    /**
     * Gets FQCN of a customizing entity.
     */
    public function getClassName(): string
    {
        return $this->get(self::CLASS_NAME);
    }

    /**
     * Sets FQCN of a customizing entity.
     */
    public function setClassName(string $className): void
    {
        $this->set(self::CLASS_NAME, $className);
    }

    /**
     * Gets the name of the action which causes this action, e.g. "create" or "update".
     */
    public function getParentAction(): ?string
    {
        $action = $this->get(self::PARENT_ACTION);

        return '' !== $action ? $action : null;
    }

    /**
     * Sets the name of the action which causes this action, e.g. "create" or "update".
     */
    public function setParentAction(?string $action): void
    {
        $this->set(self::PARENT_ACTION, $action ?? '');
    }

    /**
     * Gets a configuration of a root entity.
     */
    public function getRootConfig(): ?EntityDefinitionConfig
    {
        return $this->rootConfig;
    }

    /**
     * Sets a configuration of a root entity.
     */
    public function setRootConfig(?EntityDefinitionConfig $config): void
    {
        $this->rootConfig = $config;
    }

    /**
     * Gets a configuration of a customizing entity.
     */
    public function getConfig(): ?EntityDefinitionConfig
    {
        return $this->config;
    }

    /**
     * Sets a configuration of a customizing entity.
     */
    public function setConfig(?EntityDefinitionConfig $config): void
    {
        $this->config = $config;
    }

    /**
     * Gets an object that is used to share data between a primary action
     * and actions that are executed as part of this action.
     * Also, this object can be used to share data between different kind of child actions.
     */
    #[\Override]
    public function getSharedData(): ParameterBagInterface
    {
        return $this->sharedData;
    }

    /**
     * Sets an object that is used to share data between a primary action
     * and actions that are executed as part of this action.
     * Also, this object can be used to share data between different kind of child actions.
     */
    #[\Override]
    public function setSharedData(ParameterBagInterface $sharedData): void
    {
        $this->sharedData = $sharedData;
    }

    /**
     * Gets a context for response data normalization.
     */
    public function getNormalizationContext(): array
    {
        $normalizationContext = [
            self::ACTION       => $this->getAction(),
            self::VERSION      => $this->getVersion(),
            self::REQUEST_TYPE => $this->getRequestType(),
            'sharedData'       => $this->getSharedData()
        ];
        $parentAction = $this->getParentAction();
        if ($parentAction) {
            $normalizationContext[self::PARENT_ACTION] = $parentAction;
        }

        return $normalizationContext;
    }
}
