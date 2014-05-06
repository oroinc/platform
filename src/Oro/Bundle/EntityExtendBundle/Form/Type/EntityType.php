<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

class EntityType extends AbstractType
{
    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    public function __construct(ExtendDbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'className',
            'text',
            [
                'label'       => 'Name',
                'block'       => 'entity',
                'subblock'    => 'second',
                'constraints' => [
                    new Assert\Length(['min' => 5, 'max' => $this->nameGenerator->getMaxCustomEntityNameSize()])
                ],
            ]
        );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'   => 'Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel',
                'block_config' => [
                    'entity' => [
                        'title'     => 'General',
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
        return 'oro_entity_extend_entity_type';
    }
}
