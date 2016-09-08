<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\EmailBundle\Form\DataTransformer\EmailTemplateTransformer;

class EmailTemplateRichTextType extends AbstractType
{
    const NAME = 'oro_email_template_rich_text';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // append template transformer to run after parent type transformers
        $builder->addModelTransformer(new EmailTemplateTransformer(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_rich_text';
    }
}
