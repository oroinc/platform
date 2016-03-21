<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * An instance of this class can be added to the config extras of the Context
 * to request a general entity information including information about fields.
 */
class EntityDefinitionConfigExtra implements ConfigExtraInterface
{
    const NAME = ConfigUtil::DEFINITION;

    /** @var string */
    protected $action;

    /**
     * @param string $action The name of the action for which the configuration is requested
     */
    public function __construct($action = null)
    {
        $this->action = $action;
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
    }

    /**
     * {@inheritdoc}
     */
    public function isInheritable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        return self::NAME;
    }
}
