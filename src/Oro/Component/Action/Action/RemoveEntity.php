<?php

namespace Oro\Component\Action\Action;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Exception\NotManageableEntityException;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Remove entity
 * Usage:
 * @remove_entity: $some.path
 */
class RemoveEntity extends AbstractAction
{
    public const NAME = 'remove_entity';

    /**
     * @var mixed
     */
    protected $target;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ContextAccessor $contextAccessor, ManagerRegistry $registry)
    {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $value = $this->contextAccessor->getValue($context, $this->target);
        if (!is_object($value)) {
            throw new InvalidParameterException(
                sprintf(
                    'Action "%s" expects reference to entity as parameter, %s is given.',
                    static::NAME,
                    gettype($value)
                )
            );
        }

        $this->getEntityManager(ClassUtils::getClass($value))->remove($value);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (1 == count($options)) {
            $this->target = reset($options);
        } else {
            throw new InvalidParameterException(
                sprintf(
                    'Parameters of "%s" action must have 1 element, but %d given',
                    static::NAME,
                    count($options)
                )
            );
        }

        return $this;
    }

    /**
     * @param string $entityClassName
     * @return EntityManager
     * @throws NotManageableEntityException
     */
    protected function getEntityManager($entityClassName)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityClassName);
        if (!$entityManager) {
            throw new NotManageableEntityException($entityClassName);
        }

        return $entityManager;
    }
}
