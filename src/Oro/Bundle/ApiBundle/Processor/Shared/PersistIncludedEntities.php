<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Makes all new included entities persistent.
 */
class PersistIncludedEntities implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

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
        /** @var FormContext $context */

        $includedData = $context->getIncludedData();
        if (empty($includedData)) {
            // no included data
            return;
        }

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            // the Context does not have included entities
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
