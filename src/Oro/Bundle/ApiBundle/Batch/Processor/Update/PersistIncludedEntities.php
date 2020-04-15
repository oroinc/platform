<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Makes all new included entities persistent for all batch items that do not have errors.
 */
class PersistIncludedEntities implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var BatchUpdateContext $context */

        $items = $context->getBatchItems();
        if (!$items) {
            return;
        }

        foreach ($items as $item) {
            $itemContext = $item->getContext();
            if (!$itemContext->hasErrors()) {
                $itemTargetContext = $itemContext->getTargetContext();
                if ($itemTargetContext instanceof FormContext) {
                    $this->persistIncludedEntities($itemTargetContext);
                }
            }
        }
    }

    /**
     * @param FormContext $context
     */
    private function persistIncludedEntities(FormContext $context): void
    {
        $includedData = $context->getIncludedData();
        if (empty($includedData)) {
            // no included data
            return;
        }

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            // the context does not have included entities
            return;
        }

        foreach ($includedEntities as $entity) {
            if (!$includedEntities->getData($entity)->isExisting()) {
                $em = $this->doctrineHelper->getEntityManager($entity, false);
                if (null !== $em) {
                    $em->persist($entity);
                }
            }
        }
    }
}
