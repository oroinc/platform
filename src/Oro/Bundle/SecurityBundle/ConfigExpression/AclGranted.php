<?php

namespace Oro\Bundle\SecurityBundle\ConfigExpression;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * Checks whether an access to a resource is granted.
 */
class AclGranted extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var mixed */
    protected $attributes;

    /** @var mixed */
    protected $object;

    /**
     * @param SecurityFacade  $securityFacade
     * @param ManagerRegistry $doctrine
     */
    public function __construct(SecurityFacade $securityFacade, ManagerRegistry $doctrine)
    {
        $this->securityFacade = $securityFacade;
        $this->doctrine       = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'acl';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $params = [$this->attributes];
        if ($this->object !== null) {
            $params[] = $this->object;
        }

        return $this->convertToArray($params);
    }

    /**
     * {@inheritdoc}
     *
     * Configuration example:
     *
     * @acl: ['contact_view']
     * @acl: ['EDIT', 'Acme\DemoBundle\Entity\Contact']
     *
     * {@see Oro\Bundle\SecurityBundle\SecurityFacade::isGranted} for details.
     */
    public function initialize(array $options)
    {
        $count = count($options);
        if ($count >= 1 && $count <= 2) {
            $this->attributes = reset($options);
            if (!$this->attributes) {
                throw new InvalidArgumentException('ACL attributes must not be empty.');
            }
            if ($count > 1) {
                next($options);
                $this->object = current($options);
                if (!$this->object) {
                    throw new InvalidArgumentException('ACL object must not be empty.');
                }
            }
        } else {
            throw new InvalidArgumentException(
                sprintf('Options must have 1 or 2 elements, but %d given.', count($options))
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $attributes = $this->resolveValue($context, $this->attributes);
        $object     = $this->resolveValue($context, $this->object);

        if (is_object($object)) {
            $class         = ClassUtils::getClass($object);
            $objectManager = $this->doctrine->getManagerForClass($class);
            if ($objectManager instanceof EntityManager) {
                $unitOfWork = $objectManager->getUnitOfWork();
                if ($unitOfWork->isScheduledForInsert($object) || !$unitOfWork->isInIdentityMap($object)) {
                    $object = 'entity:' . $class;
                }
            }
        }

        return $this->securityFacade->isGranted($attributes, $object);
    }
}
