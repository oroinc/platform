<?php

namespace Oro\Component\Action\Action;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\ConfigExpression\ContextAccessor;

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
    public const OPTION_KEY_ENTITY = 'entity';

    /**
     * @var mixed
     */
    protected $entity;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ContextAccessor $contextAccessor, ManagerRegistry $registry)
    {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
    }

    #[\Override]
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
     * @throws \Exception
     */
    #[\Override]
    protected function executeAction($context)
    {
        $entity = $this->getEntity($context);

        if ($entity === null) {
            return;
        }

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

        $this->refreshEntity($entity, $entityManager);
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

    private function refreshEntity(object $entity, EntityManagerInterface $entityManager): void
    {
        $class = $entityManager->getClassMetadata(ClassUtils::getClass($entity));
        $uow = $entityManager->getUnitOfWork();

        $uow->getEntityPersister($class->name)->refresh(
            array_combine($class->getIdentifierFieldNames(), $uow->getEntityIdentifier($entity)),
            $entity,
        );
    }
}
