<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class FieldType extends AbstractType
{
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
                'block'       => 'type',
                'constraints' => [
                    new Assert\Length(['min' => 2, 'max' => $this->nameGenerator->getMaxCustomEntityFieldNameSize()])
                ],
            ]
        );

        $entityProvider = $this->configManager->getProvider('entity');
        $extendProvider = $this->configManager->getProvider('extend');

        $entityConfig = $extendProvider->getConfig($options['class_name']);
        if ($entityConfig->is('relation')) {
            $types = [];
            foreach ($entityConfig->get('relation') as $relationKey => $relation) {
                $fieldId       = $relation['field_id'];
                $targetFieldId = $relation['target_field_id'];

                if (!$relation['assign'] || !$targetFieldId) {
                    continue;
                }

                if ($fieldId
                    && $extendProvider->hasConfigById($fieldId)
                    && !$extendProvider->getConfigById($fieldId)->is('state', ExtendScope::STATE_DELETED)
                ) {
                    continue;
                }

                $entityLabel = $entityProvider->getConfig($targetFieldId->getClassName())->get('label');
                $fieldLabel  = $entityProvider->getConfigById($targetFieldId)->get('label');

                $key         = $relationKey . '||' . ($fieldId ? $fieldId->getFieldName() : '');
                $types[$key] = sprintf(
                    '%s (%s) %s',
                    $this->translator->trans('Relation'),
                    $this->translator->trans($entityLabel),
                    $this->translator->trans($fieldLabel)
                );
            }

            $this->types = array_merge($this->types, $types);
        }

        $builder->add(
            'type',
            'choice',
            [
                'choices'     => $this->types,
                'empty_value' => 'Select field type',
                'block'       => 'type',
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
                        'type' => [
                            'title'    => 'General',
                            'priority' => 1,
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
}
