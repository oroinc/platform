<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\EmailBundle\Api\Repository\EmailContextEntityRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads entities available for the email context API resources.
 */
class LoadEmailContextEntities implements ProcessorInterface
{
    private EmailContextEntityRepository $repository;

    public function __construct(EmailContextEntityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $context->setResult(
            $this->repository->getEntities(
                $context->getVersion(),
                $context->getRequestType(),
                $context->getFilterValues()->get('allowed')?->getValue()
            )
        );
    }
}
