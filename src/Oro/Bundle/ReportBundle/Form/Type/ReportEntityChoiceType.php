<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;

/**
 * Form type for selecting entities in report configuration.
 *
 * Extends {@see EntityChoiceType} to provide a specialized entity selection form field
 * for use in report creation and editing, with a custom block prefix for styling
 * and identification in report forms.
 */
class ReportEntityChoiceType extends EntityChoiceType
{
    #[\Override]
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_report_entity_choice';
    }
}
