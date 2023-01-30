<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update\JsonApi;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\CompleteItemErrorPaths as BaseCompleteItemErrorPaths;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Psr\Log\LoggerInterface;

/**
 * Checks if there are any errors in the contexts of batch items,
 * and if so, completes paths for these errors.
 */
class CompleteItemErrorPaths extends BaseCompleteItemErrorPaths
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function completeItemErrorPath(
        Error $error,
        BatchUpdateItem $item,
        int $itemOffset,
        ?string $sectionName
    ): void {
        $errorSource = $error->getSource();
        if (null === $errorSource) {
            $errorSource = new ErrorSource();
            $error->setSource($errorSource);
        }
        $pointer = $errorSource->getPointer();
        if ($pointer) {
            if (str_starts_with($pointer, '/' . $sectionName)) {
                $pointer = sprintf('/%s/%s%s', $sectionName, $itemOffset, substr($pointer, \strlen($sectionName) + 1));
            } elseif ('/' . JsonApiDoc::INCLUDED !== $pointer) {
                $includedData = $item->getIncludedData();
                if (null !== $includedData && str_starts_with($pointer, '/' . JsonApiDoc::INCLUDED . '/')) {
                    $pointer = substr($pointer, \strlen(JsonApiDoc::INCLUDED) + 2);
                    $endPosOfIncludeIndex = strpos($pointer, '/');
                    if (false !== $endPosOfIncludeIndex) {
                        $includeIndex = substr($pointer, 0, $endPosOfIncludeIndex);
                        $pointer = substr($pointer, \strlen($includeIndex));
                    } else {
                        $includeIndex = $pointer;
                        $pointer = '';
                    }
                    $includeIndex = (int)$includeIndex;
                    $itemData = $item->getContext()->getRequestData();
                    if (isset($itemData[JsonApiDoc::INCLUDED][$includeIndex])) {
                        $includedItem = $itemData[JsonApiDoc::INCLUDED][$includeIndex];
                        $includedItemOffset = $includedData->getIncludedItemIndex(
                            $includedItem[JsonApiDoc::TYPE],
                            $includedItem[JsonApiDoc::ID]
                        );
                        if (null !== $includedItemOffset) {
                            $itemOffset = $includedItemOffset;
                            $sectionName = JsonApiDoc::INCLUDED;
                        } else {
                            $pointer = '';
                            $this->logger->error(
                                'Failed to compute a correct pointer for an included item'
                                . ' because the item cannot be found in the included item index.',
                                [
                                    'requestData' => $itemData,
                                    'itemType'    => $includedItem[JsonApiDoc::TYPE],
                                    'itemId'      => $includedItem[JsonApiDoc::ID]
                                ]
                            );
                        }
                    } else {
                        $pointer = '';
                        $this->logger->error(
                            'Failed to compute a correct pointer for an included item'
                            . ' because the item does not exist in a request data.',
                            ['requestData' => $itemData, 'itemIndex' => $includeIndex]
                        );
                    }
                }
                $pointer = sprintf('/%s/%s%s', $sectionName, $itemOffset, $pointer);
            }
        } else {
            $pointer = sprintf('/%s/%s', $sectionName, $itemOffset);
        }
        $errorSource->setPointer($pointer);
    }
}
