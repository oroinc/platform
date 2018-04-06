<?php

namespace Oro\Component\Action\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Oro\Component\Action\Exception\NotManageableEntityException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Creates a managed entity clone
 */
class CloneEntity extends CloneObject
{
    const OPTION_KEY_FLUSH = 'flush';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var FlashBagInterface */
    protected $flashBag;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ContextAccessor $contextAccessor
     * @param ManagerRegistry $registry
     * @param TranslatorInterface $translator
     * @param FlashBagInterface $flashBag
     * @param LoggerInterface $logger
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        ManagerRegistry $registry,
        TranslatorInterface $translator,
        FlashBagInterface $flashBag = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($contextAccessor);

        $this->registry   = $registry;
        $this->translator = $translator;
        $this->flashBag   = $flashBag;
        $this->logger     = $logger != null ? $logger : new NullLogger();
    }

    /** {@inheritdoc} */
    protected function cloneObject($context)
    {
        $target = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_TARGET]);
        /** @var EntityManager $entityManager */
        $entityClassName = ClassUtils::getClass($target);
        $entityManager = $this->getEntityManager($entityClassName);
        $entity = parent::cloneObject($context);

        // avoid duplicate ids
        $classMeta = $entityManager->getClassMetadata($entityClassName);
        $targetId = $classMeta->getIdentifierValues($target);
        $entityId = $classMeta->getIdentifierValues($entity);

        if ($targetId == $entityId) {
            $classMeta->setIdentifierValues($entity, array_fill_keys(array_keys($entityId), null));
        }

        $saved = false;
        try {
            // save
            $entityManager->persist($entity);

            if ($this->doFlush()) {
                $entityManager->flush($entity);
            }
            $saved = true;
        } catch (\Exception $e) {
            $saved = false;
            if ($this->flashBag) {
                $this->flashBag->add('error', $this->translator->trans('oro.action.clone.error'));
            }

            $this->logger->error($e->getMessage());
        }

        if ($saved && $this->flashBag) {
            $this->flashBag->add('success', $this->translator->trans('oro.action.clone.success'));
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
