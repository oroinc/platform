<?php

namespace Oro\Bundle\FlexibleEntityBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Oro\Bundle\FlexibleEntityBundle\AttributeType\AbstractAttributeType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Oro\Bundle\FlexibleEntityBundle\Form\EventListener\AttributeTypeSubscriber;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Type for attribute form
 */
class AttributeType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->addFieldId($builder);

        $this->addFieldCode($builder);

        $this->addFieldAttributeType($builder);

        $this->addFieldRequired($builder);

        $this->addFieldUnique($builder);

        $this->addFieldTranslatable($builder);

        $this->addFieldScopable($builder);

        $this->addFieldSearchable($builder);

        $this->addFieldDefaultValue($builder);

        $this->addPositionField($builder);

        $this->addSubscriber($builder);
    }

    /**
     * Add subscriber
     * @param FormBuilderInterface $builder
     */
    protected function addSubscriber(FormBuilderInterface $builder)
    {
        $factory = $builder->getFormFactory();
        $subscriber = new AttributeTypeSubscriber($factory);
        $builder->addEventSubscriber($subscriber);
    }

    /**
     * Add field id to form builder
     * @param FormBuilderInterface $builder
     */
    protected function addFieldId(FormBuilderInterface $builder)
    {
        $builder->add('id', HiddenType::class);
    }

    /**
     * Add field code to form builder
     * @param FormBuilderInterface $builder
     */
    protected function addFieldCode(FormBuilderInterface $builder)
    {
        $builder->add('code', TextType::class, array('required' => true));
    }

    /**
     * Add field frontend type to form builder
     * @param FormBuilderInterface $builder
     */
    protected function addFieldAttributeType(FormBuilderInterface $builder)
    {
        $builder->add('attributeType', ChoiceType::class, array('choices' => $this->getAttributeTypeChoices()));
    }

    /**
     * Add field required to form builder
     * @param FormBuilderInterface $builder
     */
    protected function addFieldRequired(FormBuilderInterface $builder)
    {
        $builder->add('required', ChoiceType::class, array('choices' => array('No', 'Yes')));
    }

    /**
     * Add field unique to form builder
     * @param FormBuilderInterface $builder
     */
    protected function addFieldUnique(FormBuilderInterface $builder)
    {
        $builder->add('unique', ChoiceType::class, array('choices' => array('No', 'Yes')));
    }

    /**
     * Add field default value to form builder
     * @param FormBuilderInterface $builder
     */
    protected function addFieldDefaultValue(FormBuilderInterface $builder)
    {
        $builder->add('default_value', TextType::class, array('required' => false));
    }

    /**
     * Add field searchable to form builder
     * @param FormBuilderInterface $builder
     */
    protected function addFieldSearchable(FormBuilderInterface $builder)
    {
        $builder->add('searchable', ChoiceType::class, array('choices' => array('No', 'Yes')));
    }

    /**
     * Add field translatable to form builder
     * @param FormBuilderInterface $builder
     */
    protected function addFieldTranslatable(FormBuilderInterface $builder)
    {
        $builder->add('translatable', ChoiceType::class, array('choices' => array('No', 'Yes')));
    }

    /**
     * Add field scopable to form builder
     * @param FormBuilderInterface $builder
     */
    protected function addFieldScopable(FormBuilderInterface $builder)
    {
        $builder->add('scopable', ChoiceType::class, array('choices' => array('No', 'Yes')));
    }

    /**
     * Add attribute position field
     * @param FormBuilderInterface $builder
     */
    protected function addPositionField(FormBuilderInterface $builder)
    {
        $builder->add('sortOrder', IntegerType::class, array('label' => 'Position'));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\FlexibleEntityBundle\Entity\Attribute'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_flexibleentity_attribute';
    }

    /**
     * Return available frontend type
     *
     * @return array
     */
    public function getAttributeTypeChoices()
    {
        $types = array(
            'oro_flexibleentity_text' => 'oro_flexibleentity_text'
        );
        asort($types);

        return $types;
    }
}
