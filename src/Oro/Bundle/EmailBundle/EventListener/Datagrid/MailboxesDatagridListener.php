<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Event\PreBuild;

class MailboxesDatagridListener
{
    const GRID_NAME = 'base-mailboxes-grid';
    const REDIRECT_DATA_KEY = 'redirectData';

    const PATH_NAME = '[name]';
    const PATH_UPDATE_LINK_DIRECT_PARAMS = '[properties][update_link][direct_params]';

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();
        $parameters = $event->getParameters();
        if ($config->offsetGetByPath(static::PATH_NAME) !== static::GRID_NAME ||
            !$parameters->has(static::REDIRECT_DATA_KEY)
        ) {
            return;
        }

        $config->offsetSetByPath(
            static::PATH_UPDATE_LINK_DIRECT_PARAMS,
            array_merge(
                $config->offsetGetByPath(static::PATH_UPDATE_LINK_DIRECT_PARAMS, []),
                [
                    static::REDIRECT_DATA_KEY => $parameters->get(static::REDIRECT_DATA_KEY)
                ]
            )
        );
    }
}
