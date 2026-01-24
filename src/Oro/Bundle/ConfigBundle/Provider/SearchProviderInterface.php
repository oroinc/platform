<?php

namespace Oro\Bundle\ConfigBundle\Provider;

/**
 * Defines the contract for providing searchable configuration data.
 *
 * Implementations of this interface are responsible for extracting searchable content
 * from different types of configuration definitions (fields, groups, sections, etc.).
 * The search data is used to enable full-text search functionality in the system
 * configuration interface, allowing users to quickly locate configuration options
 * by searching for relevant keywords, labels, and values.
 */
interface SearchProviderInterface
{
    /**
     * Determines whether this provider is applicable for the given name
     *
     * @param string $name
     *
     * @return bool
     */
    public function supports($name);

    /**
     * Returns configuration search data by given name
     *
     * @param string $name
     *
     * @return array
     */
    public function getData($name);
}
