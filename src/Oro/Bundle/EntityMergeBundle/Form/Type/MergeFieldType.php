<?php

namespace Oro\Bundle\EntityMergeBundle\Form\Type;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\Options;

use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class MergeFieldType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var FieldMetadata $metadata */
        $metadata = $options['metadata'];

        $builder->add(
            'sourceEntity',
            'entity',
            array(
                'class'                   => $metadata->getEntityMetadata()->getClassName(),
                'choices'                 => $options['entities'],
                'multiple'                => false,
                'expanded'                => true,
                'choices_as_values'       => true,
                'ownership_disabled'      => true,
                'dynamic_fields_disabled' => true,
            )
        );

        $mergeModes = $metadata->getMergeModes();

        if (count($mergeModes) > 1) {
            $builder->add(
                'mode',
                'choice',
                array(
                    'choices'  => $this->getMergeValues($mergeModes),
                    'multiple' => false,
                    'expanded' => false,
                    'label'    => 'oro.entity_merge.form.strategy',
                    'tooltip'  => 'oro.entity_merge.form.strategy.tooltip'
                )
            );
        } else {
            $builder->add(
                'mode',
                'hidden',
                array('data' => $mergeModes ? MergeModes::REPLACE : current($mergeModes))
            );
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var FieldData $fieldData */
            $fieldData = $event->getData();
            if (!$fieldData->getSourceEntity()) {
                $fieldData->setSourceEntity($fieldData->getEntityData()->getMasterEntity());
            }
        });
    }

    /**
     * Get values of merge modes with labels
     *
     * @param array $modes
     * @return array
     */
    protected function getMergeValues(array $modes)
    {
        $result = array();

        foreach ($modes as $mode) {
            $result[$mode] = $this->translator->trans('oro.entity_merge.merge_modes.' . $mode);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $offset = 0;
        foreach ($view->children['sourceEntity']->children as $child) {
            $child->vars['block_prefixes'] = array_merge(
                $child->vars['block_prefixes'],
                array('oro_entity_merge_choice_value')
            );
            $child->vars['merge_entity_offset'] = $offset++;
            $child->vars['merge_field_data'] = $view->vars['value'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array(
                'metadata',
                'entities',
            )
        );

        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\\Bundle\\EntityMergeBundle\\Data\\FieldData'
            )
        );

        $resolver->setAllowedTypes(
            array(
                'metadata' => 'Oro\\Bundle\\EntityMergeBundle\\Metadata\\FieldMetadata',
                'entities' => 'array',
            )
        );

        $resolver->setNormalizers(
            array(
                'label' => function (Options $options, $value) {
                    if (!$value) {
                        $value = $options['metadata']->get('label');
                    }
                    return $value;
                }
            )
        );
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
        return 'oro_entity_merge_field';
    }
}
