<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\UnitOfWork;
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
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        $additionalEntities = $context->getAdditionalEntities();
        foreach ($additionalEntities as $entity) {
            $this->persistEntity($entity, true);
        }

        $includedEntities = $context->getIncludedEntities();
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
}
