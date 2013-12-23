<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;

class ReportEntityChoiceType extends EntityChoiceType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_report_entity_choice';
    }
}
