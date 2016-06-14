<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\LocaleBundle\Form\DataTransformer\LocalizedFallbackValueCollectionTransformer;

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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            self::FIELD_VALUES,
            LocalizedPropertyType::NAME,
            ['type' => $options['type'], 'options' => $options['options']]
        )->add(
            self::FIELD_IDS,
            'collection',
            ['type' => 'hidden']
        );

        $builder->addViewTransformer(
            new LocalizedFallbackValueCollectionTransformer($this->registry, $options['field'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'field'   => 'string', // field used to store data - string or text
            'type'    => 'text',   // value form type
            'options' => [],       // value form options
        ]);
    }
}
