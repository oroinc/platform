<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\Criteria;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

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

        if (null !== $context->getCriteria()) {
            // the Criteria object is already initialized
            return;
        }

        $context->setCriteria(new Criteria($this->entityClassResolver));
    }
}
