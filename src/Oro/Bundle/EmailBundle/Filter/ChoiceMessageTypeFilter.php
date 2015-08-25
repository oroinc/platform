<?php

namespace Oro\Bundle\EmailBundle\Filter;

use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\EmailBundle\Model\FolderType;

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

        if (in_array(FolderType::INBOX, $data['value']) && in_array(FolderType::SENT, $data['value'])) {
            $data['value'] = [];
        } elseif (in_array(FolderType::INBOX, $data['value'])) {
            $data['value'][] = FolderType::SPAM;
            $data['value'][] = FolderType::OTHER;
            $data['value'][] = FolderType::TRASH;
        } elseif (in_array(FolderType::SENT, $data['value'])) {
            $data['value'][] = FolderType::DRAFTS;
        }

        return parent::apply($ds, $data);
    }
}
