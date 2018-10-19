<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FunctionChoiceType extends AbstractType
{
    const NAME = 'oro_function_choice';

    /** @var Manager */
    protected $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }


    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $manager = $this->manager;
        $queryType = $options['query_type'];

        $view->vars['page_component_options'] = array_merge(
            $options['page_component_options'],
            [
                'converters' => $manager->getMetadataForFunctions('converters', $queryType),
                'aggregates' => $manager->getMetadataForFunctions('aggregates', $queryType),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined('page_component_name');
        $resolver->setRequired(['page_component', 'page_component_options', 'query_type']);
        $resolver->setAllowedTypes('page_component', 'string');
        $resolver->setAllowedTypes('page_component_name', 'string');
        $resolver->setAllowedTypes('page_component_options', 'array');

        $resolver->setDefaults([
            'choices'                => [],
            'placeholder'            => 'oro.query_designer.form.choose_function',
            'empty_data'             => '',
            'page_component'         => 'oroui/js/app/components/view-component',
            'page_component_options' => [
                'view'         => 'oroquerydesigner/js/app/views/function-choice-view',
                'autoRender'   => true,
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($options['page_component_name'])) {
            $view->vars['attr']['data-page-component-name'] = $options['page_component_name'];
        }
        $view->vars['attr']['data-page-component-module'] = $options['page_component'];
        $view->vars['attr']['data-page-component-options'] = json_encode($view->vars['page_component_options']);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
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
}
