<?php

namespace Oro\Bundle\TagBundle\Filter;

use Oro\Bundle\TagBundle\Form\Type\Filter\TagsReportFilterType;

class TagsReportFilter extends AbstractTagsFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return TagsReportFilterType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClassName()
    {
        return $this->params['entityClass'];
    }
}
