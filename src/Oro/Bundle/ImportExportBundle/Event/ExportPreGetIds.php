<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event class for oro.import_export.before_get_ids event
 */
class ExportPreGetIds extends Event
{
    /** @var QueryBuilder */
    private $qb;

    /** @var array */
    private $options;

    public function __construct(QueryBuilder $qb, array $options)
    {
        $this->qb = $qb;
        $this->options = $options;
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
