<?php

namespace Oro\Bundle\EntityExtendBundle\Grid\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AjaxAction;

class AjaxRestoreFieldAction extends AjaxAction
{
    /**
     * @return array
     */
    #[\Override]
    public function getOptions()
    {
        $options = parent::getOptions();

        $options['frontend_type'] = 'ajaxrestorefield';

        return $options;
    }
}
