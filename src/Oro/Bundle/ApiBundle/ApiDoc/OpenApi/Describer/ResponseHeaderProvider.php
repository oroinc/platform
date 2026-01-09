<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;

use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;
use Oro\Bundle\ApiBundle\Processor\Create\Rest\SetLocationHeader;
use Oro\Bundle\ApiBundle\Processor\DeleteList\SetDeletedCountHeader;
use Oro\Bundle\ApiBundle\Processor\Shared\SetTotalCountHeader;
use Oro\Bundle\ApiBundle\Processor\UpdateList\SetContentLocationHeader;
use Oro\Bundle\ApiBundle\Request\ApiAction;

/**
 * Provides response headers for a specific API resource.
 */
class ResponseHeaderProvider implements ResponseHeaderProviderInterface
{
    private ResourceInfoProviderInterface $resourceInfoProvider;

    public function __construct(ResourceInfoProviderInterface $resourceInfoProvider)
    {
        $this->resourceInfoProvider = $resourceInfoProvider;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    #[\Override]
    public function getResponseHeaders(
        string $action,
        ?string $entityType,
        ?string $associationName,
        bool $isErrorResponse = false
    ): array {
        if ($isErrorResponse) {
            return [];
        }
        $headers = [];
        if (
            ApiAction::CREATE === $action
            && $entityType
            && ($associationName || !$this->resourceInfoProvider->isResourceWithoutIdentifier($entityType))
        ) {
            $headers[SetLocationHeader::RESPONSE_HEADER_NAME] = [
                'description' => 'The URL of a newly created API resource.'
            ];
        }
        if (
            ApiAction::GET_LIST === $action
            || ApiAction::DELETE_LIST === $action
            || ApiAction::GET_SUBRESOURCE === $action
            || ApiAction::GET_RELATIONSHIP === $action
        ) {
            $headers[SetTotalCountHeader::RESPONSE_HEADER_NAME] = [
                'type'        => Util::TYPE_INTEGER,
                'description' => 'The total number of entities. Returned when'
                    . ' the total count was requested by "X-Include: totalCount" request header.'
            ];
        }
        if (ApiAction::DELETE_LIST === $action) {
            $headers[SetDeletedCountHeader::RESPONSE_HEADER_NAME] = [
                'type'        => Util::TYPE_INTEGER,
                'description' => 'The total number of deleted entities. Returned when'
                    . ' the total count was requested by "X-Include: deletedCount" request header.'
            ];
        }
        if (ApiAction::UPDATE_LIST === $action) {
            $headers[SetContentLocationHeader::RESPONSE_HEADER_NAME] = [
                'description' => 'The URL of API resource provides details of'
                    . ' an asynchronous operation created to process submitted data.'
            ];
        }

        return $headers;
    }
}
