<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Adds "first", "prev" and "next" pagination links to a whole document of success response.
 * @link https://jsonapi.org/format/#fetching-pagination
 */
class AddPaginationLinks extends AbstractAddPaginationLinks
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $documentBuilder = $context->getResponseDocumentBuilder();
        if (null === $documentBuilder || !$context->isSuccessResponse()) {
            return;
        }

        $requestType = $context->getRequestType();
        $baseLink = $this->getRouteLinkMetadata(
            $this->getRoutes($requestType)->getListRouteName(),
            ['entity' => $documentBuilder->getEntityAlias($context->getClassName(), $requestType)]
        );
        $this->addLinks(
            $documentBuilder,
            $baseLink,
            $this->getFilterNames($requestType)->getPageNumberFilterName(),
            $context->getFilterValues()
        );
    }
}
