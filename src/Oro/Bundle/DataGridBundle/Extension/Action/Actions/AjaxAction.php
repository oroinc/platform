<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

/**
 * Represents an AJAX-based datagrid action.
 *
 * This action executes asynchronously via AJAX requests without full page reloads,
 * providing a smoother user experience for operations on datagrid rows.
 */
class AjaxAction extends AbstractAction
{
    /**
     * @var array
     */
    protected $requiredOptions = ['link'];

    /**
     * @return array
     */
    #[\Override]
    public function getOptions()
    {
        $options = parent::getOptions();

        $options['frontend_type'] = 'ajax';

        if (empty($options['frontend_handle'])) {
            $options['frontend_handle'] = 'ajax';
        }

        return $options;
    }
}
