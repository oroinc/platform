<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * The interface for classes that can resolve different kind of predefined identifiers of Data API resources.
 */
interface EntityIdResolverInterface
{
    /**
     * Gets the description of a predefined identifier of Data API resource that can be resolved by this resolver.
     * This description is used in auto-generated documentation, including API sandbox.
     * The Markdown markup language can be used in the description.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Gets an entity identifier corresponds to a predefined identifier this resolvers is responsible for.
     *
     * A composite entity identifier is returned as an array in the following format:
     * [field => value, ...]
     *
     * @return mixed The identifier of an entity or NULL if it cannot be resolved
     */
    public function resolve();
}
