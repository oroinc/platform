<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

class AjaxDeleteAction extends AjaxAction
{
    /**
     * @return array
     */
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
