<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Form\Type;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchEntityFilterType extends AbstractType
{
    const NAME = 'oro_search_type_entity_filter';

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var LocalizationHelper */
    protected $localizationHelper;

    /** @var Localization */
    protected $currentLocalization = false;

    /**
     * @param EntityNameResolver $entityNameResolver
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(EntityNameResolver $entityNameResolver, LocalizationHelper $localizationHelper)
    {
        $this->entityNameResolver = $entityNameResolver;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'field_options' => [
                    'multiple' => true,
                    'choice_label' => [$this, 'getLocalizedChoiceLabel'],
                ],
                'choices' => null,
            ]
        );

        // this normalizer allows to add/override field_options options outside
        $resolver->setNormalizer(
            'field_options',
            function (Options $options, $value) {
                $value['class'] = $options['class'] ?? null;
                $value['choices'] = $options['choices'] ?? null;

                return $value;
            }
        );
    }

    /**
     * @param object $entity
     * @return string
     */
    public function getLocalizedChoiceLabel($entity)
    {
        $localization = $this->getCurrentLocalization();

        return $this->entityNameResolver->getName($entity, null, $localization);
    }

    /**
     * @return Localization
     */
    protected function getCurrentLocalization()
    {
        if (false === $this->currentLocalization) {
            $this->currentLocalization = $this->localizationHelper->getCurrentLocalization();
        }

        return $this->currentLocalization;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return EntityFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
