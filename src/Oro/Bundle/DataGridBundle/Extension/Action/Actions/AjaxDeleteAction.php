<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

/**
 * Represents an AJAX-based delete action for datagrid rows.
 *
 * This specialized AJAX action is configured specifically for delete operations,
 * typically including confirmation dialogs and appropriate frontend handling.
 */
class AjaxDeleteAction extends AjaxAction
{
    /**
     * @return array
     */
    #[\Override]
    public function getOptions()
    {
        $options = parent::getOptions();

        $options['frontend_type'] = 'ajaxdelete';

        if (empty($options['frontend_handle'])) {
            $options['frontend_handle'] = 'ajaxdelete';
        }

        return $options;
    }
}
