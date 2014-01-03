<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityFieldChoiceType;

class ReportEntityFieldChoiceType extends EntityFieldChoiceType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_report_entity_field_choice';
    }
}
