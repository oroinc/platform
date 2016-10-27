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

        $includedObjects = $context->getIncludedObjects();
        if (null === $includedObjects) {
            // the Context does not have included objects
            return;
        }

        foreach ($includedObjects as $object) {
            if (!$includedObjects->getData($object)->isExisting()) {
                $em = $this->doctrineHelper->getEntityManager($object, false);
                if (null !== $em) {
                    $em->persist($object);
                }
            }
        }
    }
}
