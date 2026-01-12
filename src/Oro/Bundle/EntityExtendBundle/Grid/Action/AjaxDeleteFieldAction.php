<?php

namespace Oro\Bundle\EntityExtendBundle\Grid\Action;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AjaxAction;

/**
 * Grid action for deleting extended entity fields via AJAX.
 *
 * This action extends the base AJAX action to provide a specialized frontend type for
 * field deletion operations. It sets the frontend type to `ajaxdeletefield` which allows
 * the client-side JavaScript to handle the deletion with appropriate UI feedback and
 * confirmation dialogs.
 */
class AjaxDeleteFieldAction extends AjaxAction
{
    /**
     * @return array
     */
    #[\Override]
    public function getOptions()
    {
        $options = parent::getOptions();

        $options['frontend_type'] = 'ajaxdeletefield';

        return $options;
    }
}
