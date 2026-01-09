<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

/**
 * Represents an action that triggers a custom JavaScript event.
 *
 * This action dispatches a configured event on the frontend, allowing custom JavaScript
 * handlers to respond to datagrid row actions with application-specific logic.
 */
class TriggerEventAction extends AbstractAction
{
    protected $requiredOptions = ['event_name'];
}
