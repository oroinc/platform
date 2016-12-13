<?php

namespace Oro\Bundle\CronBundle\Migrations\Schema\v2_0;

use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;

class SchemaColumnDefinitionListener
{
    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $eventArgs)
    {
        $tableColumn = array_change_key_case($eventArgs->getTableColumn());
        $field = isset($tableColumn['field']) ? $tableColumn['field'] : '';

        // Prevent of portable table column definition (DC2Type:jms_job_safe_object)
        if ($eventArgs->getTable() === 'jms_jobs' && strtolower($field) === 'stacktrace') {
            $eventArgs->preventDefault();
        }
    }
}
