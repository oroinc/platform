<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * An interface for the different kind of providers that get the request type depended API documentation.
 */
interface DocumentationProviderInterface
{
    /**
     * Gets a documentation that is suitable for the given request type.
     *
     * @param RequestType $requestType
     *
     * @return string|null A string contains the documentation in Markdown format
     */
    public function getDocumentation(RequestType $requestType): ?string;
}
