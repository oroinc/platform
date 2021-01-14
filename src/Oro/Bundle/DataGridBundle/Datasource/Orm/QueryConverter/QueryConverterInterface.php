<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm\QueryConverter;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

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
