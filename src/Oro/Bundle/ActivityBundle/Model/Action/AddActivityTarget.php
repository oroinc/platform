<?php

namespace Oro\Bundle\ActivityBundle\Model\Action;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

/**
 * Class AddActivityTarget
 *
 * @add_activity_target:
 *      activity_entity: $.activityEntity
 *      target_entity: $.targetEntity
 *      attribute: $.attribute              # status if activity was added is stored in this optional attribute
 *
 * @package Oro\Bundle\ActivityBundle\Model\Action
 */
class AddActivityTarget extends AbstractAction
{
    /** @var ActivityManager */
    protected $activityManager;
    /** @var string */
    protected $activityEntity;
    /** @var string */
    protected $targetEntity;
    /** @var string */
    protected $attribute = null;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ActivityManager $activityManager
     */
    public function __construct(ContextAccessor $contextAccessor, ActivityManager $activityManager)
    {
        parent::__construct($contextAccessor);
        $this->activityManager = $activityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $activityEntity = $this->contextAccessor->getValue($context, $this->activityEntity);
        $targetEntity = $this->contextAccessor->getValue($context, $this->targetEntity);

        $result = $this->activityManager->addActivityTarget($activityEntity, $targetEntity);

        if ($this->attribute !== null) {
            $this->contextAccessor->setValue($context, $this->attribute, $result);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if ((count($options) < 2) || (count($options) > 3)) {
            throw new InvalidParameterException('Two or three parameters are required.');
        }

        if (isset($options['activity_entity'])) {
            $this->activityEntity = $options['activity_entity'];
        } elseif (isset($options[0])) {
            $this->activityEntity = $options[0];
        } else {
            throw new InvalidParameterException('Parameter "activity_entity" has to be set.');
        }

        if (isset($options['target_entity'])) {
            $this->targetEntity = $options['target_entity'];
        } elseif (isset($options[1])) {
            $this->targetEntity = $options[1];
        } else {
            throw new InvalidParameterException('Parameter "target_entity" has to be set.');
        }

        if (isset($options['attribute'])) {
            $this->attribute = $options['attribute'];
        } elseif (isset($options[2])) {
            $this->attribute = $options[2];
        }
    }
}
