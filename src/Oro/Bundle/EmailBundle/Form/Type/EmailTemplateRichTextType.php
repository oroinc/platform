<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\EmailBundle\Form\DataTransformer\EmailTemplateTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form type for rich text editing of email template content.
 *
 * Extends the rich text form type to provide email template-specific functionality,
 * including template variable transformation for email template composition.
 */
class EmailTemplateRichTextType extends AbstractType
{
    public const NAME = 'oro_email_template_rich_text';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // append template transformer to run after parent type transformers
        $builder->addModelTransformer(new EmailTemplateTransformer(), true);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroRichTextType::class;
    }
}
