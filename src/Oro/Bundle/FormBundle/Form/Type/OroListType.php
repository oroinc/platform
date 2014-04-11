<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToStringTransformer;

class OroListType extends TextareaType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->getPreSubmitClosure());
        $builder->addModelTransformer(new ArrayToStringTransformer("\n", true));
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
        return 'textarea';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_list';
    }
}
