<?php

namespace Oro\Bundle\TranslationBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Form\ChoiceList\TranslationChoiceLoader;
use Oro\Bundle\TranslationBundle\Form\DataTransformer\CollectionToArrayTransformer;
use Symfony\Bridge\Doctrine\Form\EventListener\MergeDoctrineCollectionListener;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for entities that support translations and ACL protection for choices.
 */
class TranslatableEntityType extends AbstractType
{
    private ManagerRegistry $doctrine;
    private ChoiceListFactoryInterface $factory;
    private AclHelper $aclHelper;

    public function __construct(
        ManagerRegistry $doctrine,
        ChoiceListFactoryInterface $choiceListFactory,
        AclHelper $aclHelper
    ) {
        $this->doctrine = $doctrine;
        $this->factory = $choiceListFactory;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['multiple']) {
            $builder
                ->addEventSubscriber(new MergeDoctrineCollectionListener())
                ->addViewTransformer(new CollectionToArrayTransformer(), true);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'query_builder' => null,
            'choice_label' => null,
            'choices' => null,
            'translatable_options' => false,
            'choice_translation_domain' => false,
            'acl_options' => ['disable' => true]
        ]);
        $resolver->setRequired(['class']);
        $resolver->setNormalizer('choice_value', function (Options $options, $value) {
            if ($value) {
                return $value;
            }

            return $this->doctrine->getManager()->getClassMetadata($options['class'])->getSingleIdentifierFieldName();
        });
        $resolver->setNormalizer('choice_loader', function (Options $options) {
            if (null !== $options['choices']) {
                return null;
            }

            return new TranslationChoiceLoader(
                $options['class'],
                $this->doctrine,
                $this->factory,
                $options['query_builder'],
                $this->aclHelper,
                $options['acl_options']
            );
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'translatable_entity';
    }
}
