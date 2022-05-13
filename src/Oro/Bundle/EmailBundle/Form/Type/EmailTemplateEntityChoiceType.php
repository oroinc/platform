<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;

/**
 * The form type to choice entities at the email template edit page.
 * Unlike EntityChoiceType, this form type shows Email entity that is excluded in entity.yml.
 */
class EmailTemplateEntityChoiceType extends EntityChoiceType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_email_template_entity_choice';
    }
}
