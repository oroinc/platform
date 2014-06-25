<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class MyEmailsGridListener
{
    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        // TODO: fix datagrid yaml definition merge in order to make possible override column twig template
        // or unset some keys from parent grid definition
        // Remove twig column configuration - field should be rendered like plain text
        $config = $event->getConfig();
        $config->offsetUnsetByPath('[columns][subject][type]');
        $config->offsetUnsetByPath('[columns][subject][frontend_type]');
        $config->offsetUnsetByPath('[columns][subject][template]');
    }
}
