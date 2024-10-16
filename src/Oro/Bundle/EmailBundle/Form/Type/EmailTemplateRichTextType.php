<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Oro\Bundle\EmailBundle\Form\DataTransformer\EmailTemplateTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EmailTemplateRichTextType extends AbstractType
{
    const NAME = 'oro_email_template_rich_text';

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
