<?php

namespace Oro\Component\Action\Action;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

use Oro\Component\Action\Exception\NotManageableEntityException;
use Oro\Component\Action\Model\ContextAccessor;

/**
 * Creates a managed entity clone
 */
class CloneEntity extends CloneObject
{
    const OPTION_KEY_FLUSH = 'flush';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var FlashBagInterface */
    protected $flashBag;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ManagerRegistry $registry
     * @param FlashBagInterface $flashBag
     * @param LoggerInterface $logger
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        ManagerRegistry $registry,
        FlashBagInterface $flashBag = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
        $this->flashBag = $flashBag;
        $this->logger   = $logger != null ? $logger : new NullLogger();
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
            if ($this->flashBag) {
                $this->flashBag()->add('error', sprintf('Could not clone entity due to an error.'));
            }

            $this->logger->error($e->getMessage());
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
        return $this->getOption($this->options, self::OPTION_KEY_FLUSH, true);
    }
}
