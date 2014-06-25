<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

use Oro\Bundle\TranslationBundle\Translation\Translator;

class FieldType extends AbstractType
{
    const ORIGINAL_FIELD_NAMES_ATTRIBUTE = 'original_field_names';

    protected $types = [
        'string'     => 'oro.entity_extend.form.data_type.string',
        'integer'    => 'oro.entity_extend.form.data_type.integer',
        'smallint'   => 'oro.entity_extend.form.data_type.smallint',
        'bigint'     => 'oro.entity_extend.form.data_type.bigint',
        'boolean'    => 'oro.entity_extend.form.data_type.boolean',
        'decimal'    => 'oro.entity_extend.form.data_type.decimal',
        'date'       => 'oro.entity_extend.form.data_type.date',
        'text'       => 'oro.entity_extend.form.data_type.text',
        'float'      => 'oro.entity_extend.form.data_type.float',
        'money'      => 'oro.entity_extend.form.data_type.money',
        'percent'    => 'oro.entity_extend.form.data_type.percent',
        'attachment'      => 'oro.entity_extend.form.data_type.attachment',
        'attachmentImage' => 'oro.entity_extend.form.data_type.attachmentImage',
        'oneToMany'  => 'oro.entity_extend.form.data_type.oneToMany',
        'manyToOne'  => 'oro.entity_extend.form.data_type.manyToOne',
        'manyToMany' => 'oro.entity_extend.form.data_type.manyToMany',
        'optionSet'  => 'oro.entity_extend.form.data_type.optionSet'
    ];

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @param ConfigManager                   $configManager
     * @param Translator                      $translator
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     */
    public function __construct(
        ConfigManager $configManager,
        Translator $translator,
        ExtendDbIdentifierNameGenerator $nameGenerator
    ) {
        $this->configManager = $configManager;
        $this->translator    = $translator;
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'fieldName',
            'text',
            [
                'label'       => 'Field Name',
                'block'       => 'general',
                'constraints' => [
                    new Assert\Length(['min' => 2, 'max' => $this->nameGenerator->getMaxCustomEntityFieldNameSize()])
                ],
            ]
        );

        $entityProvider = $this->configManager->getProvider('entity');
        $extendProvider = $this->configManager->getProvider('extend');
        $entityConfig   = $extendProvider->getConfig($options['class_name']);

        if ($entityConfig->is('relation')) {
            $originalFieldNames = array();
            $types     = [];
            $relations = $entityConfig->get('relation');
            foreach ($relations as $relationKey => $relation) {
                if (!$this->isAvailableRelation($extendProvider, $relation, $relationKey)) {
                    continue;
                }

                /** @var FieldConfigId $fieldId */
                $fieldId = $relation['field_id'];
                /** @var FieldConfigId $targetFieldId */
                $targetFieldId = $relation['target_field_id'];

                $entityLabel = $entityProvider->getConfig($targetFieldId->getClassName())->get('label');
                $fieldLabel  = $entityProvider->getConfigById($targetFieldId)->get('label');
                $fieldName   = $fieldId ? $fieldId->getFieldName() : '';

                $maxFieldNameLength = $this->nameGenerator->getMaxCustomEntityFieldNameSize();
                if (strlen($fieldName) > $maxFieldNameLength) {
                    $cutFieldName = substr($fieldName, 0, $maxFieldNameLength);
                    $originalFieldNames[$cutFieldName] = $fieldName;
                    $fieldName = $cutFieldName;
                }

                $key         = $relationKey . '||' . $fieldName;
                $types[$key] = sprintf(
                    '%s (%s) %s',
                    $this->translator->trans('Relation'),
                    $this->translator->trans($entityLabel),
                    $this->translator->trans($fieldLabel)
                );
            }

            $this->types = array_merge($this->types, $types);
            $builder->setAttribute(self::ORIGINAL_FIELD_NAMES_ATTRIBUTE, $originalFieldNames);
        }

        $builder->add(
            'type',
            'choice',
            [
                'choices'     => $this->types,
                'empty_value' => 'Select field type',
                'block'       => 'general',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(['class_name'])
            ->setDefaults(
                [
                    'require_js'   => [],
                    'block_config' => [
                        'general' => [
                            'title'    => $this->translator->trans('oro.entity_config.form.block.general'),
                            'priority' => 10,
                        ]
                    ]
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_field_type';
    }

    /**
     * Check if reverse relation can be created
     *
     * @param ConfigProvider $extendProvider
     * @param array          $relation
     * @param string         $relationKey
     *
     * @return bool
     */
    protected function isAvailableRelation(
        ConfigProvider $extendProvider,
        array $relation,
        $relationKey
    ) {
        /** @var FieldConfigId|false $fieldId */
        $fieldId = $relation['field_id'];
        /** @var FieldConfigId $targetFieldId */
        $targetFieldId = $relation['target_field_id'];

        if (!$relation['assign'] || !$targetFieldId) {
            if (!$targetFieldId) {
                return false;
            }

            // additional check for revers relation of manyToOne field type
            $targetEntityConfig = $extendProvider->getConfig($targetFieldId->getClassName());
            if (false === (!$relation['assign']
                    && !$fieldId
                    && $targetFieldId
                    && $targetFieldId->getFieldType() == 'manyToOne'
                    && $targetEntityConfig->get('relation')
                    && $targetEntityConfig->get('relation')[$relationKey]['assign']
                )
            ) {
                return false;
            }
        }

        if ($fieldId
            && $extendProvider->hasConfigById($fieldId)
            && !$extendProvider->getConfigById($fieldId)->is('state', ExtendScope::STATE_DELETED)
        ) {
            return false;
        }

        return true;
    }
}
