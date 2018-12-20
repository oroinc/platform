<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * An instance of this class can be added to the config extras of the context
 * to request a general entity information including information about fields.
 */
class EntityDefinitionConfigExtra implements ConfigExtraInterface
{
    const NAME = ConfigUtil::DEFINITION;

    /** @var string|null */
    protected $action;

    /** @var bool */
    protected $collection;

    /** @var string|null */
    protected $parentClassName;

    /** @var string|null */
    protected $associationName;

    /**
     * @param string|null $action          The name of the action for which the configuration is requested
     * @param bool        $collection      A flag indicates whether a configuration is requested
     *                                     for a list of entities resource or a single entity resource
     * @param string|null $parentClassName The class name of the parent entity
     *                                     for which the configuration is requested
     * @param string|null $associationName The association name of a sub-resource
     *                                     for which the configuration is requested
     */
    public function __construct(
        $action = null,
        $collection = false,
        $parentClassName = null,
        $associationName = null
    ) {
        $this->action = $action;
        $this->collection = $collection;
        $this->parentClassName = $parentClassName;
        $this->associationName = $associationName;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ConfigContext $context)
    {
        $context->setTargetAction($this->action);
        $context->setIsCollection($this->collection);
        $context->setParentClassName($this->parentClassName);
        $context->setAssociationName($this->associationName);
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        $result = self::NAME;
        if ($this->action) {
            $result .= ':' . $this->action;
        }
        if ($this->collection) {
            $result .= ':collection';
        }
        if ($this->parentClassName) {
            $result .= ':' . $this->parentClassName;
        }
        if ($this->associationName) {
            $result .= ':' . $this->associationName;
        }

        return $result;
    }
}
