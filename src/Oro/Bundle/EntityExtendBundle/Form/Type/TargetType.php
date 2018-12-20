<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType as RelationTypeBase;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TargetType extends AbstractType
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $this->preSetData($event, $options['field_config_id']);
        });
    }

    /**
     * Sets selected target entity class
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event, FieldConfigId $fieldConfigId)
    {
        $event->setData($this->getTargetEntityClass($fieldConfigId));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('field_config_id');
        $resolver->setAllowedTypes('field_config_id', FieldConfigId::class);
        $resolver->setDefaults(
            array(
                'attr'        => array(
                    'class' => 'extend-rel-target-name',
                ),
                'label'       => 'oro.entity_extend.form.target_entity',
                'choice_attr' => function ($choice) {
                    return $this->getChoiceAttributes($choice);
                },
                'configs' => array(
                    'allowClear'              => true,
                    'placeholder'             => 'oro.entity.form.choose_entity',
                    'result_template_twig'    => 'OroEntityBundle:Choice:entity/result.html.twig',
                    'selection_template_twig' => 'OroEntityBundle:Choice:entity/selection.html.twig',
                )
            )
        );

        $resolver->setNormalizer('attr', function (Options $options, $value) {
            $value['readonly'] = (bool) $this->getTargetEntityClass($options['field_config_id']);

            return $value;
        });

        $resolver->setNormalizer('placeholder', function (Options $options) {
            return $this->getTargetEntityClass($options['field_config_id']) ? null : '';
        });

        $resolver->setNormalizer('choices', function (Options $options) {
            return $this->getEntityChoiceList($options['field_config_id']);
        });
    }

    /**
     * @param FieldConfigId $fieldConfigId
     * @return array
     */
    protected function getEntityChoiceList(FieldConfigId $fieldConfigId)
    {
        $relationType = $fieldConfigId->getFieldType();
        $targetEntityClass = $this->getTargetEntityClass($fieldConfigId);

        /** @var EntityConfigId[] $entityIds */
        $entityIds = $targetEntityClass
            ? [$this->configManager->getId('extend', $targetEntityClass)]
            : $this->configManager->getIds('extend');

        if ($relationType === RelationTypeBase::ONE_TO_MANY) {
            $entityIds = array_filter(
                $entityIds,
                function (EntityConfigId $configId) {
                    $config = $this->configManager->getConfig($configId);

                    return $config->is('is_extend');
                }
            );
        }

        $entityIds = array_filter(
            $entityIds,
            function (EntityConfigId $configId) use ($targetEntityClass) {
                $config = $this->configManager->getConfig($configId);

                return $this->isSuitableAsTarget($config, $targetEntityClass);
            }
        );

        $choices = [];
        foreach ($entityIds as $entityId) {
            $className = $entityId->getClassName();
            $entityConfig = $this->configManager->getProvider('entity')->getConfig($className);
            $choices[$entityConfig->get('label')] = $className;
        }

        return $choices;
    }

    /**
     * @param FieldConfigId $fieldConfigId
     * @return string
     */
    private function getTargetEntityClass(FieldConfigId $fieldConfigId)
    {
        return $this->configManager
            ->getProvider('extend')
            ->getConfigById($fieldConfigId)
            ->get('target_entity');
    }

    /**
     * Returns a list of choice attributes for the given entity
     *
     * @param string $entityClass
     *
     * @return array
     */
    protected function getChoiceAttributes($entityClass)
    {
        $entityConfig = $this->configManager->getProvider('entity')->getConfig($entityClass);

        return [
            'data-icon' => $entityConfig->get('icon')
        ];
    }

    /**
     * Checks if entity is suitable as target for relation
     * @param ConfigInterface $config
     * @param string $targetEntityClass
     * @return bool
     */
    protected function isSuitableAsTarget(ConfigInterface $config, $targetEntityClass)
    {
        return
            !$config->is('is_extend')
            ||
            (!$config->is('state', ExtendScope::STATE_NEW)
                && (
                    $targetEntityClass
                    || !$config->is('is_deleted')
                ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return Select2ChoiceType::class;
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
        return 'oro_entity_target_type';
    }
}
