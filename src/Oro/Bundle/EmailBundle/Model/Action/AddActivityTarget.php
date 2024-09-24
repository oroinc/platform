<?php

namespace Oro\Bundle\EmailBundle\Model\Action;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * The action to add an entity to the context of an email entity.
 *
 * @add_email_activity_target:
 *      email: $.emailEntity
 *      target_entity: $.targetEntity
 *      attribute: $.attribute              # status if activity was added is stored in this optional attribute
 */
class AddActivityTarget extends AbstractAction
{
    private ActivityManager $activityManager;
    private mixed $activityEntity = null;
    private mixed $targetEntity = null;
    private mixed $attribute = null;

    public function __construct(ContextAccessor $contextAccessor, ActivityManager $activityManager)
    {
        parent::__construct($contextAccessor);
        $this->activityManager = $activityManager;
    }

    #[\Override]
    protected function executeAction($context): void
    {
        $email = $this->contextAccessor->getValue($context, $this->activityEntity);
        $targetEntity = $this->contextAccessor->getValue($context, $this->targetEntity);

        $result = $this->activityManager->addActivityTarget($email, $targetEntity);

        if ($this->attribute !== null) {
            $this->contextAccessor->setValue($context, $this->attribute, $result);
        }
    }

    #[\Override]
    public function initialize(array $options): void
    {
        if ((\count($options) < 2) || (\count($options) > 3)) {
            throw new InvalidParameterException('Two or three parameters are required.');
        }

        if (isset($options['email'])) {
            $this->activityEntity = $options['email'];
        } elseif (isset($options[0])) {
            $this->activityEntity = $options[0];
        } else {
            throw new InvalidParameterException('Parameter "email" has to be set.');
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
