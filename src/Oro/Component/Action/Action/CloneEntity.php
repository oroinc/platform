<?php

namespace Oro\Component\Action\Action;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\Action\Exception\ActionException;
use Oro\Component\Action\Exception\NotManageableEntityException;
use Oro\Component\Action\Model\ContextAccessor;

class CloneEntity extends CloneObject
{
    const OPTION_KEY_FLUSH = 'flush';

    /** @var ManagerRegistry */
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

    /** {@inheritdoc} */
    protected function cloneObject($context)
    {
        $target = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_TARGET]);
        /** @var EntityManager $entityManager */
        $entityClassName = ClassUtils::getClass($target);
        $entityManager = $this->getEntityManager($entityClassName);

        if (!$entityManager) {
            throw new NotManageableEntityException($entityClassName);
        }

        $entity = parent::cloneObject($context);

        // avoid duplicate ids
        $classMeta = $entityManager->getClassMetadata($entityClassName);
        $targetId = $classMeta->getIdentifierValues($target);
        $entityId = $classMeta->getIdentifierValues($entity);

        if ($targetId == $entityId) {
            $classMeta->setIdentifierValues($entity, array_fill_keys(array_keys($entityId), null));
        }

        try {
            // save
            $entityManager->persist($entity);

            if ($this->doFlush()) {
                $entityManager->flush($entity);
            }
        } catch (\Exception $e) {
            throw new ActionException(
                sprintf('Can\'t create entity %s. %s', $entityClassName, $e->getMessage())
            );
        }

        return $entity;
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

    /**
     * Whether perform flush immediately after entity creation or later
     *
     * @return bool
     */
    protected function doFlush()
    {
        return $this->getOption($this->options, self::OPTION_KEY_FLUSH, false);
    }
}
