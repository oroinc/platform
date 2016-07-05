<?php

namespace Oro\Bundle\EmailBundle\Api\Processor\GetSubresource;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\FilterHelper;
use Oro\Bundle\ApiBundle\Model\EntityDescriptor;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivitySuggestionApiEntityManager;

class LoadEmailSuggestions implements ProcessorInterface
{
    /** @var EmailActivitySuggestionApiEntityManager */
    protected $apiEntityManager;

    /**
     * @param EmailActivitySuggestionApiEntityManager $apiEntityManager
     */
    public function __construct(EmailActivitySuggestionApiEntityManager $apiEntityManager)
    {
        $this->apiEntityManager = $apiEntityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        if ($context->hasResult()) {
            // the suggestions are already loaded
            return;
        }

        $suggestions = [];
        $filterHelper = new FilterHelper($context->getFilters(), $context->getFilterValues());
        $data = $this->apiEntityManager->getSuggestionResult(
            $context->getParentId(),
            $filterHelper->getPageNumber(),
            $filterHelper->getPageSize(),
            (bool)$filterHelper->getBooleanFilterValue('exclude-current-user')
        );
        foreach ($data['result'] as $item) {
            $suggestions[] = new EntityDescriptor($item['id'], $item['entity'], $item['title']);
        }
        $context->setResult($suggestions);
    }
}
