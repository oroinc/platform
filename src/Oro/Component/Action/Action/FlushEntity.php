<?php

namespace Oro\Component\Action\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Oro\Component\Action\Model\ContextAccessor;

/**
 * Flush entity
 *
 * Usage:
 * Flush context entity
 *
 * @flush_entity: ~
 *
 * Flush entity stored in some attribute
 *
 * @flush_entity: $.someEntity
 *
 * Or
 *
 * @flush_entity:
 *     entity: $.someEntity
 */
class FlushEntity extends AbstractAction
{
    const OPTION_KEY_ENTITY = 'entity';

    /**
     * @var mixed
     */
    protected $entity;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ManagerRegistry $registry
     */
    public function __construct(ContextAccessor $contextAccessor, ManagerRegistry $registry)
    {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists(self::OPTION_KEY_ENTITY, $options)) {
            $this->entity = $options[self::OPTION_KEY_ENTITY];
        } elseif (count($options) === 1) {
            $this->entity = reset($options);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    protected function executeAction($context)
    {
        $entity = $this->getEntity($context);

        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass(ClassUtils::getClass($entity));
        $entityManager->beginTransaction();

        try {
            $entityManager->persist($entity);
            $entityManager->flush($entity);
            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }
    }

    /**
     * @param mixed $context
     * @return object|null
     */
    protected function getEntity($context)
    {
        return $this->entity
            ? $this->contextAccessor->getValue($context, $this->entity)
            : $context->getEntity();
    }
}
