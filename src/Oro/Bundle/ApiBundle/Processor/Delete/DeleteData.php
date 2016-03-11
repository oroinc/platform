<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Deletes object by entity manager.
 */
class DeleteData implements ProcessorInterface
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
        /** @var DeleteContext $context */

        if (!$context->hasResult()) {
            // entity already deleted
            return;
        }

        $object = $context->getResult();

        if (!is_object($object)) {
            // entity already deleted or not supported
            return;
        }

        $em = $this->doctrineHelper->getEntityManager($object);
        $em->remove($object);
        $em->flush();
        $context->removeResult();
    }
}
