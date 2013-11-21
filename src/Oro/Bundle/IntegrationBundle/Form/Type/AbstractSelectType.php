<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\TransportTypeInterface;

abstract class AbstractSelectType extends AbstractType
{
    const TYPE_FIELD  = 'type';
    const TYPE_OPTION = 'channelType';

    /** @var TypesRegistry */
    protected $registry;

    public function __construct(TypesRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $types   = $this->getTypesArray($options);
        $values  = $types->map(
            function (TransportTypeInterface $type) {
                return $type->getLabel();
            }
        )->toArray();
        $choices = array_combine($types->getKeys(), $values);

        $builder->add(
            self::TYPE_FIELD,
            'choice',
            [
                'label'       => 'Type',
                'choices'     => $choices,
                'constraints' => new NotBlank()
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['data_class' => null])
            ->setRequired([self::TYPE_OPTION]);
    }

    /**
     * @param array $options
     *
     * @return ArrayCollection
     */
    abstract protected function getTypesArray($options);
}
