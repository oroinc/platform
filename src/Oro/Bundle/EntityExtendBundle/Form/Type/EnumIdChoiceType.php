<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Enum id choice type.
 */
class EnumIdChoiceType extends AbstractType
{
    const NAME = 'oro_enum_id_choice';

    /** @var ManagerRegistry */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $className = EnumOption::class;
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass($className);

        $transformer = $options['multiple']
            ? new EntitiesToIdsTransformer($em, $className)
            : new EntityToIdTransformer($em, $className);

        $builder->addModelTransformer(new ReversedTransformer($transformer));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['enum_code']);
        $resolver->setDefaults(['multiple' => true]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return EnumChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
