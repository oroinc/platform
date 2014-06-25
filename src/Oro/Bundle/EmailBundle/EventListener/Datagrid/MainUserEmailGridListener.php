<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

/**
 * For the main user's email grid. For the logged in user it is My Emails menu
 */
class MainUserEmailGridListener
{
    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        // TODO: fix datagrid yaml definition merge in order to make possible override column twig template
        // or unset some keys from parent grid definition
        // https://magecore.atlassian.net/browse/BAP-4655
        // Remove twig column configuration - field should be rendered like plain text
        $config = $event->getConfig();
        $config->offsetUnsetByPath('[columns][subject][type]');
        $config->offsetUnsetByPath('[columns][subject][frontend_type]');
        $config->offsetUnsetByPath('[columns][subject][template]');
    }
}
