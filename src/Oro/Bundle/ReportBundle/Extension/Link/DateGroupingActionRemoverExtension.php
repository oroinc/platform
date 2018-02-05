<?php

namespace Oro\Bundle\ReportBundle\Extension\Link;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

class DateGroupingActionRemoverExtension extends AbstractExtension
{
    /**
     * @var string
     */
    protected $calendarEntityClass;

    /**
     * @param string $calendarEntityClass
     */
    public function __construct($calendarEntityClass)
    {
        $this->calendarEntityClass = $calendarEntityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return (
            parent::isApplicable($config)
            && $config->offsetExists('source')
            && isset($config->offsetGet('source')['query']['from'][0]['table'])
            && $this->calendarEntityClass === $config->offsetGet('source')['query']['from'][0]['table']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $rows = $result->getData();
        foreach ($rows as $key => $row) {
            $row['action_configuration']['view'] = false;
            $row['action_configuration']['update'] = false;
            $row['action_configuration']['delete'] = false;
            $rows[$key] = $row;
        }

        $result->setData($rows);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return -50;
    }
}
