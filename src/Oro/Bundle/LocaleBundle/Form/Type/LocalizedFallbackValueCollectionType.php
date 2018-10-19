<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Manage collection of localized localized fields.
 */
class LocalizedFallbackValueCollectionType extends AbstractType
{
    const NAME = 'oro_locale_localized_fallback_value_collection';

    const FIELD_VALUES = 'values';
    const FIELD_IDS    = 'ids';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            self::FIELD_VALUES,
            LocalizedPropertyType::class,
            [
                'entry_type' => $options['entry_type'],
                'entry_options' => $options['entry_options'],
                'exclude_parent_localization' => $options['exclude_parent_localization']]
        )->add(
            self::FIELD_IDS,
            CollectionType::class,
            ['entry_type' => HiddenType::class]
        );

        $builder->addViewTransformer(
            new LocalizedFallbackValueCollectionTransformer($this->registry, $options['field'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'field' => 'string', // field used to store data - string or text
            'entry_type' => TextType::class,   // value form type
            'entry_options' => [],       // value form options
            'exclude_parent_localization' => false
        ]);
    }
}
