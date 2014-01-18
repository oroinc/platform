<?php

namespace Oro\Bundle\EntityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Translation\TranslatorInterface;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

class EntityFieldSelectType extends AbstractType
{
    const NAME = 'oro_entity_field_select';

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
        $that = $this;

        $defaultConfigs = array(
            'placeholder'             => 'oro.entity.form.choose_entity_field',
            'result_template_twig'    => 'OroEntityBundle:Select:entity_field/result.html.twig',
            'selection_template_twig' => 'OroEntityBundle:Select:entity_field/selection%s.html.twig',
            'extra_config'            => 'entity_field_select',
            'extra_modules'           => ['EntityFieldUtil' => 'oro/entity-field-select-util']
        );

        $configsNormalizer = function (Options $options, $configs) use (&$defaultConfigs, $that) {
            return $that->configsNormalizer($options, $configs, $defaultConfigs);
        };
        $attrNormalizer    = function (Options $options, $attr) use ($that) {
            return $that->attrNormalizer($options, $attr);
        };

        $resolver->setDefaults(
            array(
                'entity'                    => null,
                'with_relations'            => false,
                'deep_level'                => 0,
                'last_deep_level_relations' => false,
                'empty_value'               => '',
                'skip_load_entities'        => false,
                'skip_load_data'            => false,
                'multiple'                  => false,
                'configs'                   => $defaultConfigs
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
        $configs['multiple'] = $options['multiple'];
        if ($options['multiple'] && $configs['placeholder'] === $defaultConfigs['placeholder']) {
            $configs['placeholder'] .= 's';
        }
        if ($configs['selection_template_twig'] === $defaultConfigs['selection_template_twig']) {
            $suffix = $options['multiple'] ? '_multiple' : '';
            if ($options['with_relations']) {
                $suffix .= '_with_relations';
            }
            $configs['selection_template_twig'] = sprintf($configs['selection_template_twig'], $suffix);
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
        $data                = empty($options['entity']) || $options['skip_load_data']
            ? array() // set empty data if entity is not specified or skip_load_data = true
            : $this->getData(
                $options['entity'],
                $options['with_relations'],
                $options['deep_level'],
                $options['last_deep_level_relations']
            );
        $attr['data-data']   = json_encode($data);

        return $attr;
    }

    /**
     * Returns source data for the given entity
     *
     * @param string $entityName             Entity name. Can be full class name or short form: Bundle:Entity.
     * @param bool   $withRelations          Indicates whether association fields should be returned as well.
     * @param int    $deepLevel              The maximum deep level of related entities.
     * @param bool   $lastDeepLevelRelations Indicates whether fields for the last deep level of related entities
     *                                       should be returned.
     * @return array
     */
    protected function getData($entityName, $withRelations, $deepLevel, $lastDeepLevelRelations)
    {
        $fields = $this->entityFieldProvider->getFields(
            $entityName,
            $withRelations,
            true,
            $deepLevel,
            $lastDeepLevelRelations
        );

        return $this->convertData($fields, $entityName, null);
    }

    /**
     * @param array  $fields
     * @param string $entityName
     * @param string $parentFieldId
     * @return array
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function convertData(array &$fields, $entityName, $parentFieldId)
    {
        $resultFields    = array();
        $resultRelations = array();
        foreach ($fields as $field) {
            $fieldId = null !== $parentFieldId
                ? sprintf('%s+%s::%s', $parentFieldId, $entityName, $field['name'])
                : $field['name'];

            $fieldData         = [];
            $fieldData['text'] = $field['label'];
            foreach ($field as $key => $val) {
                if (!in_array($key, ['label', 'related_entity_fields'])) {
                    $fieldData[$key] = $val;
                }
            }

            if (!isset($field['related_entity_name'])) {
                $fieldData['id'] = $fieldId;
                $resultFields[]  = $fieldData;
            } else {
                if (isset($field['related_entity_fields'])) {
                    $fieldData['children'] = $this->convertData(
                        $field['related_entity_fields'],
                        $field['related_entity_name'],
                        $fieldId
                    );
                }
                $resultRelations[] = $fieldData;
            }
        }

        $result = array();
        if (!empty($resultFields)) {
            if (null === $parentFieldId && empty($resultRelations)) {
                $result = $resultFields;
            } else {
                $result[] = [
                    'text'     => $this->translator->trans('oro.entity.form.entity_fields'),
                    'children' => $resultFields
                ];
            }
        }
        if (!empty($resultRelations)) {
            foreach ($resultRelations as $resultRelation) {
                $result[] = $resultRelation;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
