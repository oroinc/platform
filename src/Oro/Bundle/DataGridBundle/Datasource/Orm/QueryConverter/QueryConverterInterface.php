<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm\QueryConverter;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Defines the contract for query converters.
 *
 * Query converters transform between different query representations, typically converting
 * YAML or array-based query definitions into Doctrine ORM {@see QueryBuilder} instances and vice versa.
 * This abstraction allows datagrids to be configured using declarative syntax.
 */
interface QueryConverterInterface
{
    /**
     * Parses a YAML string to a QueryBuilder object.
     *
     * @param  string|array    $value    A YAML string or structured associative array
     * @param  ManagerRegistry $doctrine The registry of entity managers
     *
     * @return QueryBuilder
     */
    public function parse($value, ManagerRegistry $doctrine);

    /**
     * Dumps a QueryBuilder object to YAML.
     *
     * @param  QueryBuilder $input
     *
     * @return string       The YAML representation of the PHP value
     */
    public function dump(QueryBuilder $input);
}
