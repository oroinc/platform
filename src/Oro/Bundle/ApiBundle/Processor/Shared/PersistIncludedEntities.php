<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ApiBundle\Collection\AdditionalEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Makes all new included entities persistent.
 */
class PersistIncludedEntities implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        $this->persistAdditionalEntities($context->getAdditionalEntityCollection());
        $this->persistIncludedEntities($context->getIncludedEntities());
    }

    private function persistAdditionalEntities(AdditionalEntityCollection $additionalEntities): void
    {
        foreach ($additionalEntities->getEntities() as $entity) {
            if ($additionalEntities->shouldEntityBeRemoved($entity)) {
                $this->removeEntity($entity);
            } else {
                $this->persistEntity($entity, true);
            }
        }
    }

    private function persistIncludedEntities(?IncludedEntityCollection $includedEntities): void
    {
        if (null !== $includedEntities) {
            foreach ($includedEntities as $entity) {
                if (!$includedEntities->getData($entity)->isExisting()) {
                    $this->persistEntity($entity);
                }
            }
        }
    }

    private function persistEntity(object $entity, bool $checkIsNew = false): void
    {
        $em = $this->doctrineHelper->getEntityManager($entity, false);
        if (null === $em) {
            return;
        }

        if ($checkIsNew && UnitOfWork::STATE_NEW !== $em->getUnitOfWork()->getEntityState($entity)) {
            return;
        }

        $em->persist($entity);
    }

    private function removeEntity(object $entity): void
    {
        $em = $this->doctrineHelper->getEntityManager($entity, false);
        if (null === $em) {
            return;
        }

        if (UnitOfWork::STATE_MANAGED !== $em->getUnitOfWork()->getEntityState($entity)) {
            return;
        }

        $em->remove($entity);
    }
}
