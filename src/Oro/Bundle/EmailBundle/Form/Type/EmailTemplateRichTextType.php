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
        $builder->resetModelTransformers();

        $allowableTags = null;
        if (!empty($options['wysiwyg_options']['valid_elements'])) {
            $allowableTags = $options['wysiwyg_options']['valid_elements'];
        }

        $transformer = new SanitizeHTMLTransformer($allowableTags, $this->cacheDir);
        $builder->addModelTransformer(new EmailTemplateTransformer($transformer));
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
