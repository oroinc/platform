<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

/**
 * Provides an interface for classes responsible to parse documentation for API resources.
 */
interface ResourceDocParserInterface
{
    /**
     * Gets a documentation for API resource.
     */
    public function getActionDocumentation(string $className, string $actionName): ?string;

    /**
     * Gets a documentation for a field.
     */
    public function getFieldDocumentation(
        string $className,
        string $fieldName,
        ?string $actionName = null
    ): ?string;

    /**
     * Gets a documentation for a filter.
     */
    public function getFilterDocumentation(string $className, string $filterName): ?string;

    /**
     * Gets a documentation for API sub-resource.
     */
    public function getSubresourceDocumentation(
        string $className,
        string $subresourceName,
        string $actionName
    ): ?string;

    /**
     * Registers a documentation resource in this parser.
     *
     * @param string $resource
     *
     * @return bool TRUE if the given resource is supported; otherwise, FALSE.
     */
    public function registerDocumentationResource(string $resource): bool;
}
