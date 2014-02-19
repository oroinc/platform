<?php

namespace Oro\Bundle\EntityMergeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\OptionsResolver\Options;

use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class MergeFieldType extends AbstractType
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
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
                'class' => $metadata->getEntityMetadata()->getClassName(),
                'choices' => $options['entities'],
                'multiple' => false,
                'expanded' => true,
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
                    'attr'     => array('class' => 'entity-merge-small-select')
                )
            );
        } else {
            $builder->add(
                'mode',
                'hidden',
                array('data' => $mergeModes ? MergeModes::REPLACE : current($mergeModes))
            );
        }
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
        return 'oro_entity_merge_field';
    }
}
