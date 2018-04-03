<?php

namespace Oro\Bundle\EntityBundle\Form\Type;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\FormBundle\Form\Type\Select2HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

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
    public function configureOptions(OptionsResolver $resolver)
    {
        $that = $this;

        $defaultConfigs = [
            'placeholder'             => 'oro.entity.form.choose_entity_field',
            'result_template_twig'    => 'OroEntityBundle:Select:entity_field/result.html.twig',
            'selection_template_twig' => 'OroEntityBundle:Select:entity_field/selection%s.html.twig',
            'component'               => 'entity-field-select'
        ];

        $configsNormalizer = function (Options $options, $configs) use (&$defaultConfigs, $that) {
            return $that->configsNormalizer($options, $configs, $defaultConfigs);
        };
        $attrNormalizer    = function (Options $options, $attr) use ($that) {
            return $that->attrNormalizer($options, $attr);
        };

        $resolver->setDefaults(
            [
                'entity'                    => null,
                'with_relations'            => false,
                'with_unidirectional'       => false,
                'with_virtual_fields'       => false,
                'placeholder'               => '',
                'skip_load_entities'        => false,
                'skip_load_data'            => false,
                'multiple'                  => false,
                'configs'                   => $defaultConfigs,
            ]
        );

        $resolver->setNormalizer('configs', $configsNormalizer)
            ->setNormalizer('attr', $attrNormalizer);
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
        $configs             = array_merge($defaultConfigs, $configs);
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
            ? [] // set empty data if entity is not specified or skip_load_data = true
            : $this->getData(
                $options['entity'],
                $options['with_relations'],
                $options['with_virtual_fields'],
                $options['with_unidirectional']
            );
        $attr['data-data']   = json_encode($data);

        return $attr;
    }

    /**
     * Returns source data for the given entity
     *
     * @param string $entityName             Entity name. Can be full class name or short form: Bundle:Entity.
     * @param bool   $withRelations          Indicates whether association fields should be returned as well.
     * @param bool   $withVirtualFields      Indicates whether virtual fields should be returned as well.
     * @param bool   $withUnidirectional     Indicates whether Unidirectional association fields should be returned.
     *                                       should be returned.
     * @return array
     */
    protected function getData($entityName, $withRelations, $withVirtualFields, $withUnidirectional)
    {
        $fields = $this->entityFieldProvider->getFields(
            $entityName,
            $withRelations,
            $withVirtualFields,
            true,
            $withUnidirectional
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
        $result = [];
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

            if (!isset($field['relation_type'])) {
                $fieldData['id'] = $fieldId;
                $result[]        = $fieldData;
            } else {
                if (isset($field['related_entity_fields'])) {
                    $fieldData['children'] = $this->convertData(
                        $field['related_entity_fields'],
                        $field['related_entity_name'],
                        $fieldId
                    );
                }
                $result[] = $fieldData;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return Select2HiddenType::class;
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
}
