<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToStringTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Form\EventListener\FixArrayToStringListener;
use Oro\Bundle\FormBundle\Form\Exception\FormException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select an entity.
 */
class EntityIdentifierType extends AbstractType
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer($this->createEntitiesToIdsTransformer($options));
        if ($options['multiple']) {
            $builder
                ->addViewTransformer(new ArrayToStringTransformer($options['values_delimiter'], true))
                ->addEventSubscriber(new FixArrayToStringListener($options['values_delimiter']));
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'em' => null,
            'property' => null,
            'queryBuilder' => null,
            'multiple' => true,
            'values_delimiter' => ','
        ]);
        $resolver->setAllowedValues('multiple', [true, false]);
        $resolver->setRequired(['class']);

        $emNormalizer = function (Options $options, $em) {
            if (null !== $em) {
                if ($em instanceof EntityManagerInterface) {
                    return $em;
                }
                if (!\is_string($em)) {
                    throw new FormException(\sprintf(
                        'Option "em" should be a string or entity manager object, %s given',
                        get_debug_type($em)
                    ));
                }
                $em = $this->doctrine->getManager($em);
            } else {
                $em = $this->doctrine->getManagerForClass($options['class']);
            }

            if (null === $em) {
                throw new FormException(\sprintf(
                    'Class "%s" is not a managed Doctrine entity. Did you forget to map it?',
                    $options['class']
                ));
            }

            return $em;
        };

        $queryBuilderNormalizer = function (Options $options, $queryBuilder) {
            if (null !== $queryBuilder && !\is_callable($queryBuilder)) {
                throw new FormException(\sprintf(
                    'Option "queryBuilder" should be a callable, %s given',
                    get_debug_type($queryBuilder)
                ));
            }

            return $queryBuilder;
        };

        $resolver->setNormalizer('em', $emNormalizer);
        $resolver->setNormalizer('queryBuilder', $queryBuilderNormalizer);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_entity_identifier';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return HiddenType::class;
    }

    private function createEntitiesToIdsTransformer(array $options): DataTransformerInterface
    {
        if ($options['multiple']) {
            return new EntitiesToIdsTransformer(
                $this->doctrine,
                $options['class'],
                $options['property'],
                $options['queryBuilder']
            );
        }

        return new EntityToIdTransformer(
            $this->doctrine,
            $options['class'],
            $options['property'],
            $options['queryBuilder']
        );
    }
}
