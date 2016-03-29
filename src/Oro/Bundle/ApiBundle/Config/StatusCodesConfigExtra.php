<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * An instance of this class can be added to the config extras of the Context
 * to request an information about possible response status codes for the specified action.
 */
class StatusCodesConfigExtra implements ConfigExtraInterface
{
    const NAME = ConfigUtil::STATUS_CODES;

    /** @var string */
    protected $action;

    /**
     * @param string $action The name of the action for which the status codes are requested
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
    public function isPropagable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        return $this->action
            ? self::NAME . ':' . $this->action
            : self::NAME;
    }
}
