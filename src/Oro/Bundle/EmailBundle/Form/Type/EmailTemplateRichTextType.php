<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;
use Oro\Bundle\EmailBundle\Form\DataTransformer\EmailTemplateTransformer;

class EmailTemplateRichTextType extends AbstractType
{
    const NAME = 'oro_email_template_rich_text';

    /** @var string */
    protected $cacheDir;

    /**
     * @param string $cacheDir
     */
    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (null !== $options['wysiwyg_options']['valid_elements']) {
            $templateTransformer = new EmailTemplateTransformer(
                new SanitizeHTMLTransformer(
                    $options['wysiwyg_options']['valid_elements'],
                    $this->cacheDir
                )
            );
            // sanitize transformer is already added in the parent type
            $templateTransformer->setSanitize(false);
            // append template transformer to run after parent type transformers
            $builder->addModelTransformer($templateTransformer, true);
        }
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
