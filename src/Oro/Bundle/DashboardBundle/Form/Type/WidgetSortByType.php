<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WidgetSortByType extends AbstractType
{
    /** @var EntityFieldProvider */
    protected $fieldProvider;

    /**
     * @param EntityFieldProvider $fieldProvider
     */
    public function __construct(EntityFieldProvider $fieldProvider)
    {
        $this->fieldProvider = $fieldProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('property', ChoiceType::class, [
                'label' => false,
                'choices' => $this->createPropertyChoices($options['class_name']),
                'required' => false,
                'placeholder' => 'oro.dashboard.widget.sort_by.property.placeholder',
            ])
            ->add('order', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    'oro.dashboard.widget.sort_by.order.asc.label' => 'ASC',
                    'oro.dashboard.widget.sort_by.order.desc.label' => 'DESC',
                ],
            ])
            ->add('className', HiddenType::class, ['data' => $options['class_name']]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('class_name');

        $resolver->setDefaults([
            'label' => 'oro.dashboard.widget.sort_by.label',
            'attr' => [
                'class' => 'widget-sort-by',
            ],
        ]);
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
        return 'oro_type_widget_sort_by';
    }

    /**
     * @param string $className
     *
     * @return array
     */
    protected function createPropertyChoices($className)
    {
        $choices = [];

        //@TODO change logic of grabbing choices. They should grab from datagrid config
        $fields = $this->fieldProvider->getFields($className);
        foreach ($fields as $field) {
            $choices[$field['label']] = $field['name'];
        }

        return $choices;
    }
}
