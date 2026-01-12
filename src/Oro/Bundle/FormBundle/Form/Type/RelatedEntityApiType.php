<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Validator\Constraints as OroAssert;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * API form type for handling related entity references.
 *
 * This type manages the relationship between entities in API requests, accepting
 * entity ID and class information. It validates that the entity class exists and
 * is manageable by Doctrine, and uses a data transformer to convert between API
 * format and entity references.
 */
class RelatedEntityApiType extends AbstractType
{
    /** @var DataTransformerInterface */
    protected $dataTransformer;

    public function __construct(DataTransformerInterface $dataTransformer)
    {
        $this->dataTransformer = $dataTransformer;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'id',
                TextType::class,
                [
                    'required'    => true,
                    'constraints' => [new Assert\NotBlank()]
                ]
            )
            ->add(
                'entity',
                TextType::class,
                [
                    'required'    => true,
                    'constraints' => [new Assert\NotBlank(), new OroAssert\EntityClass()]
                ]
            );
        $builder->addModelTransformer($this->dataTransformer);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'allow_extra_fields' => true,
                'error_bubbling'     => false,
                'constraints'        => [new OroAssert\RelatedEntity()]
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_related_entity_api';
    }
}
