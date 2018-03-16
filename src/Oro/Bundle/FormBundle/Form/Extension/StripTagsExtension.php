<?php

namespace Oro\Bundle\FormBundle\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StripTagsExtension extends AbstractTypeExtension
{
    use FormExtendedTypeTrait;

    const OPTION_NAME = 'strip_tags';

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

        if (!empty($options[self::OPTION_NAME])) {
            $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->onPreSubmit());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(self::OPTION_NAME);
    }

    /**
     * @return \Closure
     */
    protected function onPreSubmit()
    {
        return function (FormEvent $event) {
            $data = $event->getData();
            if (is_string($data)) {
                $event->setData($this->htmlTagHelper->stripTags($data));
            }
        };
    }
}
