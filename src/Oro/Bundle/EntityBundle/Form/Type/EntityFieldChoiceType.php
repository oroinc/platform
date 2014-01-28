<?php

namespace Oro\Bundle\EntityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\FormBundle\Form\Type\ChoiceListItem;

class EntityFieldChoiceType extends AbstractType
{
    const NAME = 'oro_entity_field_choice';

    /**
     * @var EntityProvider
     */
    protected $entityProvider;

    /**
     * @var EntityFieldProvider
     */
    protected $entityFieldProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Constructor
     *
     * @param EntityProvider      $entityProvider
     * @param EntityFieldProvider $entityFieldProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EntityProvider $entityProvider,
        EntityFieldProvider $entityFieldProvider,
        TranslatorInterface $translator
    ) {
        $this->entityProvider      = $entityProvider;
        $this->entityFieldProvider = $entityFieldProvider;
        $this->translator          = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $that    = $this;
        $choices = function (Options $options) use ($that) {
            return empty($options['entity']) || $options['skip_load_data']
                ? [] // return empty list if entity is not specified or skip_load_data = true
                : $that->getChoices(
                    $options['entity'],
                    $options['with_relations']
                );
        };

        $defaultConfigs = array(
            'is_translated_option'    => true,
            'placeholder'             => 'oro.entity.form.choose_entity_field',
            'result_template_twig'    => 'OroEntityBundle:Choice:entity_field/result.html.twig',
            'selection_template_twig' => 'OroEntityBundle:Choice:entity_field/selection.html.twig',
            'extra_config'            => 'entity_field_choice',
            'extra_modules'           => ['EntityFieldUtil' => 'oro/entity-field-choice-util']
        );

        $configsNormalizer = function (Options $options, $configs) use (&$defaultConfigs, $that) {
            return $that->configsNormalizer($options, $configs, $defaultConfigs);
        };
        $attrNormalizer    = function (Options $options, $attr) use ($that) {
            return $that->attrNormalizer($options, $attr);
        };

        $resolver->setDefaults(
            array(
                'entity'             => null,
                'with_relations'     => false,
                'choices'            => $choices,
                'empty_value'        => '',
                'skip_load_entities' => false,
                'skip_load_data'     => false,
                'configs'            => $defaultConfigs
            )
        );
        $resolver->setNormalizers(
            array(
                'configs' => $configsNormalizer,
                'attr'    => $attrNormalizer
            )
        );
    }

    /**
     * Applies dynamic attributes for 'configs' options
     *
     * @param Options $options
     * @param array   $configs
     * @param array   $defaultConfigs
     * @return array
     */
    protected function configsNormalizer(Options $options, &$configs, &$defaultConfigs)
    {
        $configs = array_merge($defaultConfigs, $configs);
        if ($options['multiple'] && $configs['placeholder'] === $defaultConfigs['placeholder']) {
            $configs['placeholder'] .= 's';
        }
        if ($options['with_relations'] && !$options['skip_load_entities']) {
            $configs['entities'] = $this->entityProvider->getEntities();
        }

        return $configs;
    }

    /**
     * Applies dynamic attributes for 'attr' options
     *
     * @param Options $options
     * @param array   $attr
     * @return array
     */
    protected function attrNormalizer(Options $options, &$attr)
    {
        $attr['data-entity'] = $options['entity'];

        return $attr;
    }

    /**
     * Returns a list of choices
     *
     * @param string $entityName    Entity name. Can be full class name or short form: Bundle:Entity.
     * @param bool   $withRelations Indicates whether association fields should be returned as well.
     * @return array of entity fields
     *                              key = field name, value = ChoiceListItem
     */
    protected function getChoices($entityName, $withRelations)
    {
        $choiceFields    = [];
        $choiceRelations = [];
        $fields          = $this->entityFieldProvider->getFields(
            $entityName,
            $withRelations,
            true
        );
        foreach ($fields as $field) {
            $attributes = [];
            foreach ($field as $key => $val) {
                if (!in_array($key, ['name', 'related_entity_fields'])) {
                    $attributes['data-' . $key] = $val;
                }
            }
            if (!isset($field['related_entity_name'])) {
                $choiceFields[$field['name']] = new ChoiceListItem($field['label'], $attributes);
            } else {
                $choiceRelations[$field['name']] = new ChoiceListItem($field['label'], $attributes);
            }
        }

        if (empty($choiceRelations)) {
            return $choiceFields;
        }
        $choices = [];
        if (!empty($choiceFields)) {
            $choices[$this->translator->trans('oro.entity.form.entity_fields')] = $choiceFields;
        }
        $choices[$this->translator->trans('oro.entity.form.entity_related')] = $choiceRelations;

        return $choices;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
