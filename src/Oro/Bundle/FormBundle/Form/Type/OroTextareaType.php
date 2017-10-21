<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Additional option can be used to strip tags for the textarea
 *
 * @deprecated will be removed in 2.5, please use form option 'strip_tags'
 * Use {@see \Oro\Bundle\FormBundle\Form\Extension\StripTagsExtension} instead
 */
class OroTextareaType extends AbstractType
{
    const NAME = 'oro_textarea';

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /**
     * {@inheritDoc}
     */
    public function __construct(HtmlTagHelper $htmlTagHelper)
    {
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->onPreSubmit());
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'strip_tags' => false,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return TextareaType::class;
    }

    /**
     * @return \Closure
     */
    protected function onPreSubmit()
    {
        return function (FormEvent $event) {
            $config = $event->getForm()->getConfig();
            if ($config->getOption('strip_tags')) {
                $event->setData(
                    $this->htmlTagHelper->stripTags($event->getData())
                );
            }
        };
    }
}
