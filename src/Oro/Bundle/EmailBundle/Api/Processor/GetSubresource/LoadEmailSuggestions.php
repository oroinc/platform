<?php

namespace Oro\Bundle\EmailBundle\Api\Processor\GetSubresource;

use Oro\Bundle\ApiBundle\Filter\FilterHelper;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\Shared\LoadTitleMetaProperty;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivitySuggestionApiEntityManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

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
        $titlePropertyPath = null;
        if (!$context->isProcessed(LoadTitleMetaProperty::OPERATION_NAME)) {
            $titlePropertyPath = ConfigUtil::getPropertyPathOfMetaProperty(
                LoadTitleMetaProperty::TITLE_META_PROPERTY_NAME,
                $context->getConfig()
            );
        }
        foreach ($data['result'] as $item) {
            $suggestion = new EntityIdentifier($item['id'], $item['entity']);
            if ($titlePropertyPath) {
                $suggestion->setAttribute($titlePropertyPath, $item['title']);
            }
            $suggestions[] = $suggestion;
        }
        if ($titlePropertyPath) {
            $context->setProcessed(LoadTitleMetaProperty::OPERATION_NAME);
        }
        $context->setResult($suggestions);
    }
}
