<?php

namespace Oro\Bundle\TagBundle\Filter;

use Oro\Bundle\TagBundle\Form\Type\Filter\TagsReportFilterType;

/**
 * The filter by tags for reports and segments.
 */
class TagsReportFilter extends TagsDictionaryFilter
{
    #[\Override]
    protected function getFormType()
    {
        return TagsReportFilterType::class;
    }

    #[\Override]
    protected function getEntityClassName()
    {
        return $this->get('entityClass');
    }
}
