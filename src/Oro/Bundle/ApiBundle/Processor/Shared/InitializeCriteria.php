<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

/**
 * Checks whether the Criteria object exists in the Context and adds it if not.
 */
class InitializeCriteria implements ProcessorInterface
{
    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /**
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(EntityClassResolver $entityClassResolver)
    {
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasResult()) {
            // data already exist
            return;
        }

        if (null === $context->getCriteria()) {
            $context->setCriteria(new Criteria($this->entityClassResolver));
        }
    }
}
