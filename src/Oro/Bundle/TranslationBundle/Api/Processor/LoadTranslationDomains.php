<?php

namespace Oro\Bundle\TranslationBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\TranslationBundle\Api\Repository\TranslationDomainRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads translation domains.
 */
class LoadTranslationDomains implements ProcessorInterface
{
    private TranslationDomainRepository $translationDomainRepository;

    public function __construct(TranslationDomainRepository $translationDomainRepository)
    {
        $this->translationDomainRepository = $translationDomainRepository;
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

        $context->setResult($this->translationDomainRepository->getTranslationDomains());
    }
}
