<?php

namespace Oro\Bundle\EmailBundle\Filter;

use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

class ChoiceMessageTypeFilter extends ChoiceFilter
{
    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        if (in_array('inbox', $data['value']) && in_array('sent', $data['value'])) {
            $data['value'] = [];
        }  else if (in_array('inbox', $data['value'])) {
            $data['value'][] = 'spam';
            $data['value'][] = 'other';
            $data['value'][] = 'trash';
        } else  if (in_array('sent', $data['value'])) {
            $data['value'][] = 'drafts';
        }

        return parent::apply($ds, $data);
    }
}
