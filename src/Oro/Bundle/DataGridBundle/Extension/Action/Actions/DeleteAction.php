<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;

/**
 * Represents a delete action for datagrid rows.
 *
 * This action provides standard delete functionality with automatic confirmation dialog
 * support to prevent accidental deletions.
 */
class DeleteAction extends AbstractAction
{
    /**
     * @var array
     */
    protected $requiredOptions = ['link'];

    #[\Override]
    public function setOptions(ActionConfiguration $options)
    {
        if (!isset($options['confirmation'])) {
            $options['confirmation'] = true;
        }

        parent::setOptions($options);
    }
}
