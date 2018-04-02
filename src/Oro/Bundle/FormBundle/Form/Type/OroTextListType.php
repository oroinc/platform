<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class OroTextListType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->getPreSubmitClosure());
        $builder->addModelTransformer(new ArrayToStringTransformer(",", true));
    }

    /**
     * Default value for list is empty array
     * but model transformer in reverse transform allow only strings,
     * this closure replaces empty array with empty string.
     *
     * @return callable
     */
    protected function getPreSubmitClosure()
    {
        return function (FormEvent $event) {
            $data = $event->getData();
            if (is_array($data) && empty($data)) {
                $event->setData('');
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
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
        return 'oro_textlist';
    }
}
