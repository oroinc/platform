<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Filter\QueryStringAccessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\PhpUtils\QueryStringUtil;

/**
 * The metadata that represents a link to the next page of API resource or sub-resource.
 */
class NextPageLinkMetadata extends LinkMetadataDecorator
{
    /** @var string */
    private $pageNumberFilterName;

    /** @var QueryStringAccessorInterface|null */
    private $queryStringAccessor;

    /**
     * @param LinkMetadataInterface             $link
     * @param string                            $pageNumberFilterName
     * @param QueryStringAccessorInterface|null $queryStringAccessor
     */
    public function __construct(
        LinkMetadataInterface $link,
        string $pageNumberFilterName,
        QueryStringAccessorInterface $queryStringAccessor = null
    ) {
        parent::__construct($link);
        $this->pageNumberFilterName = $pageNumberFilterName;
        $this->queryStringAccessor = $queryStringAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getHref(DataAccessorInterface $dataAccessor): ?string
    {
        if (!$this->hasMoreRecords($dataAccessor)) {
            return null;
        }

        $pageNumber = null;
        if (!$dataAccessor->tryGetValue(ConfigUtil::PAGE_NUMBER, $pageNumber)
            && null !== $this->queryStringAccessor
        ) {
            // the pagination is not supported
            return null;
        }
        if (null === $pageNumber) {
            $pageNumber = 1;
        }

        $nextPageNumber = $pageNumber + 1;
        $baseUrl = parent::getHref($dataAccessor);
        $queryString = null !== $this->queryStringAccessor
            ? $this->queryStringAccessor->getQueryString()
            : '';
        $queryString = QueryStringUtil::addParameter(
            $queryString,
            $this->pageNumberFilterName,
            (string)$nextPageNumber
        );

        return QueryStringUtil::addQueryString($baseUrl, $queryString);
    }

    /**
     * @param DataAccessorInterface $dataAccessor
     *
     * @return bool
     */
    private function hasMoreRecords(DataAccessorInterface $dataAccessor): bool
    {
        $hasMore = null;
        if (!$dataAccessor->tryGetValue(ConfigUtil::HAS_MORE, $hasMore)) {
            return false;
        }

        return $hasMore;
    }
}
