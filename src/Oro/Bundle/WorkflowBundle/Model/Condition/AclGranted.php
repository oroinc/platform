<?php

namespace Oro\Bundle\WorkflowBundle\Model\Condition;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\WorkflowBundle\Exception\ConditionException;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class AclGranted extends AbstractCondition
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var mixed
     */
    protected $attributes;

    /**
     * @var object
     */
    protected $object;

    /**
     * @param ContextAccessor $contextAccessor
     * @param SecurityFacade $securityFacade
     */
    public function __construct(ContextAccessor $contextAccessor, SecurityFacade $securityFacade)
    {
        $this->contextAccessor = $contextAccessor;
        $this->securityFacade = $securityFacade;
    }

    /**
     * Always return TRUE
     *
     * @param mixed $context
     * @return boolean
     */
    protected function isConditionAllowed($context)
    {
        return $this->securityFacade->isGranted(
            $this->contextAccessor->getValue($context, $this->attributes),
            $this->contextAccessor->getValue($context, $this->object)
        );
    }

    /**
     * Nothing to initialize
     *
     * @param array $options
     * @return ConditionInterface
     * @throws ConditionException If options passed
     */
    public function initialize(array $options)
    {
        if (empty($options)) {
            throw new ConditionException('Condition requires ACL attributes');
        }

        $this->attributes = array_shift($options);
        if (!$this->attributes) {
            throw new ConditionException('ACL attributes can not be empty');
        }

        if ($options) {
            $this->object = array_shift($options);
            if (!$this->object) {
                throw new ConditionException('ACL object can not be empty');
            }
        }

        return $this;
    }
}
