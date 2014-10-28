<?php

namespace Oro\Bundle\EntityExtendBundle\Grid\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AjaxAction;

class AjaxDeleteFieldAction extends AjaxAction
{
    /**
     * @return array
     */
    public function getOptions()
    {
        $options = parent::getOptions();

        $options['frontend_type'] = 'ajaxdeletefield';

        if (empty($options['frontend_handle'])) {
            $options['frontend_handle'] = 'ajaxdeletefield';
        }

        return $options;
    }
}
