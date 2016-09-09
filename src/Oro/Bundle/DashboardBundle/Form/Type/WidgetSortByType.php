<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

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
            ->add('property', 'choice', [
                'label' => false,
                'choices' => $this->createPropertyChoices($options['class_name']),
                'required' => false,
                'placeholder' => 'oro.dashboard.widget.sort_by.property.placeholder',
            ])
            ->add('order', 'choice', [
                'label' => false,
                'choices' => [
                    'ASC' => 'oro.dashboard.widget.sort_by.order.asc.label',
                    'DESC' => 'oro.dashboard.widget.sort_by.order.desc.label',
                ],
            ])
            ->add('className', 'hidden', ['data' => $options['class_name']]);
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

        $fields = $this->fieldProvider->getFields($className);
        foreach ($fields as $field) {
            $choices[$field['name']] = $field['label'];
        }

        return $choices;
    }
}
