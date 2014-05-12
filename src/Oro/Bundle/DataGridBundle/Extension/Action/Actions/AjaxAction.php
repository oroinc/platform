<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

class AjaxAction extends AbstractAction
{
    /**
     * @var array
     */
    protected $requiredOptions = ['link'];

    /**
     * @return array
     */
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
