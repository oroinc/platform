<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\EntityCreateOrSelectTransformer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class OroEntityCreateOrSelectChoiceType extends AbstractType
{
    const NAME = 'oro_entity_create_or_select_choice';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($options) {
                $data = $event->getData();
                $mode = !empty($data['mode']) ? $data['mode'] : $options['mode'];
                if ($mode != OroEntityCreateOrSelectType::MODE_CREATE) {
                    $this->disableNewEntityValidation($event->getForm(), $options);
                }
            }
        );

        $builder->addViewTransformer(
            new EntityCreateOrSelectTransformer($this->doctrineHelper, $options['class'], $options['mode'])
        );

        $builder->add(
            'existing_entity',
            $options['select_entity_form_type'],
            array_merge(
                ['required' => $options['required']],
                $options['select_entity_form_options']
            )
        );

        $builder->add(
            'new_entity',
            $options['create_entity_form_type'],
            $this->getNewEntityFormOptions($options)
        );

        $builder->add('mode', 'hidden', [
            'data' => $options['mode'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired([
            'class',
            'create_entity_form_type',
            'select_entity_form_type',
        ]);

        $resolver->setDefaults([
            'data_class' => null,
            'create_entity_form_options' => [],
            'select_entity_form_options' => [],
            'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getNewEntityFormOptions(array $options)
    {
        return array_merge(
            $options['create_entity_form_options'],
            [
                'required' => $options['required'],
                'data_class' => $options['class'],
            ]
        );
    }

    /**
     * @param FormInterface $form
     * @param array $options
     */
    protected function disableNewEntityValidation(FormInterface $form, array $options)
    {
        $form->remove('new_entity');
        $form->add(
            'new_entity',
            $options['create_entity_form_type'],
            array_merge(
                $this->getNewEntityFormOptions($options),
                ['validation_groups' => false]
            )
        );
    }
}
