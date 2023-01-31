<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\CompleteDescriptions;

use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * The helper that is used to get the documentation parser for a specific request type.
 */
class ResourceDocParserProvider
{
    /** @var ResourceDocParserInterface[] */
    private array $resourceDocParsers = [];
    private ResourceDocParserRegistry $resourceDocParserRegistry;

    public function __construct(ResourceDocParserRegistry $resourceDocParserRegistry)
    {
        $this->resourceDocParserRegistry = $resourceDocParserRegistry;
    }

    public function getResourceDocParser(RequestType $requestType): ResourceDocParserInterface
    {
        $cacheKey = (string)$requestType;
        if (isset($this->resourceDocParsers[$cacheKey])) {
            return $this->resourceDocParsers[$cacheKey];
        }

        $parser = $this->resourceDocParserRegistry->getParser($requestType);
        $this->resourceDocParsers[$cacheKey] = $parser;

        return $parser;
    }
}
