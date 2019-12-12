<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event class for oro.import_export.before_get_ids event
 */
class ExportPreGetIds extends Event
{
    /** @var QueryBuilder */
    private $qb;

    /** @var array */
    private $options;

    /**
     * @param QueryBuilder $qb
     * @param array $options
     */
    public function __construct(QueryBuilder $qb, array $options)
    {
        $this->qb = $qb;
        $this->options = $options;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
