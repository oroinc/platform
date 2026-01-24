<?php

namespace Oro\Bundle\EntityExtendBundle\Grid\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AjaxAction;

/**
 * Grid action for restoring deleted extended entity fields via AJAX.
 *
 * This action extends the base AJAX action to provide a specialized frontend type for
 * field restoration operations. It sets the frontend type to `ajaxrestorefield` which allows
 * the client-side JavaScript to handle the restoration with appropriate UI feedback and
 * confirmation dialogs.
 */
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
