<?php

declare(strict_types=1);

namespace Oro\Bundle\DataGridBundle\Extension\Action;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

/**
 * General interface for action configurators.
 */
interface DataGridActionConfiguratorInterface
{
    public function getConfiguration(ResultRecordInterface $record, array $actions = []): array;
}
