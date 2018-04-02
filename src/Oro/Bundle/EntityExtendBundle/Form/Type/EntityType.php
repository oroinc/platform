<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EntityType extends AbstractType
{
    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     */
    public function __construct(ExtendDbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'className',
            TextType::class,
            [
                'label'       => 'oro.entity_extend.form.name.label',
                'block'       => 'general',
                'subblock'    => 'second',
                'constraints' => [
                    new Assert\Length(['min' => 5, 'max' => $this->nameGenerator->getMaxCustomEntityNameSize()])
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'   => 'Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel',
                'block_config' => [
                    'general' => [
                        'subblocks' => [
                            'second' => [
                                'priority' => 10
                            ]
                        ]
                    ]
                ]
            ]
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
        return 'oro_entity_extend_entity_type';
    }
}
