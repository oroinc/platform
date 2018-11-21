<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Filter\QueryStringAccessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\PhpUtils\QueryStringUtil;

/**
 * The metadata that represents a link to the previous page of API resource.
 */
class PrevPageLinkMetadata extends LinkMetadataDecorator
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
        $pageNumber = null;
        if (!$dataAccessor->tryGetValue(ConfigUtil::PAGE_NUMBER, $pageNumber)) {
            // the pagination is not supported
            return null;
        }
        if ($pageNumber <= 1) {
            // the link to the previous page is not needed
            return null;
        }

        $prevPageNumber = $pageNumber - 1;
        $baseUrl = parent::getHref($dataAccessor);
        $queryString = null !== $this->queryStringAccessor
            ? $this->queryStringAccessor->getQueryString()
            : '';
        if ($prevPageNumber > 1) {
            $queryString = QueryStringUtil::addParameter(
                $queryString,
                $this->pageNumberFilterName,
                (string)$prevPageNumber
            );
        } else {
            $queryString = QueryStringUtil::removeParameter($queryString, $this->pageNumberFilterName);
        }

        return QueryStringUtil::addQueryString($baseUrl, $queryString);
    }
}
