<?php

namespace Oro\Bundle\WorkflowBundle\Model\Condition;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\WorkflowBundle\Exception\ConditionException;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;
use Oro\Bundle\WorkflowBundle\Model\DoctrineHelper;

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
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var mixed
     */
    protected $attributes;

    /**
     * @var object
     */
    protected $objectOrClass;

    /**
     * @param ContextAccessor $contextAccessor
     * @param SecurityFacade $securityFacade
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        SecurityFacade $securityFacade,
        DoctrineHelper $doctrineHelper
    ) {
        $this->contextAccessor = $contextAccessor;
        $this->securityFacade = $securityFacade;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Check ACL for resource.
     *
     * @param mixed $context
     * @return boolean
     */
    protected function isConditionAllowed($context)
    {
        $attributes = $this->contextAccessor->getValue($context, $this->attributes);
        $objectOrClass = $this->contextAccessor->getValue($context, $this->objectOrClass);

        if (is_object($objectOrClass)) {
            $unitOfWork = $this->doctrineHelper->getEntityManager($objectOrClass)->getUnitOfWork();
            if (!$unitOfWork->isInIdentityMap($objectOrClass) || $unitOfWork->isScheduledForInsert($objectOrClass)) {
                $objectOrClass = 'Entity:' . $this->doctrineHelper->getEntityClass($objectOrClass);
            }
        }

        return $this->securityFacade->isGranted($attributes, $objectOrClass);
    }

    /**
     * Initialize options.
     *
     * Configuration example:
     *      @acl_granted: ['contact_view']
     *      @acl_granted: ['EDIT', 'Acme\DemoBundle\Entity\Contact']
     *
     * @param array $options
     * @return ConditionInterface
     * @throws ConditionException
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
            $this->objectOrClass = array_shift($options);
            if (!$this->objectOrClass) {
                throw new ConditionException('ACL object can not be empty');
            }
        }

        return $this;
    }
}
