<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType as RelationTypeBase;

class TargetType extends AbstractType
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var FieldConfigId */
    protected $configId;

    /** @var string|null */
    protected $targetEntityClass;

    /**
     * @param ConfigManager $configManager
     * @param FieldConfigId $configId
     */
    public function __construct(ConfigManager $configManager, FieldConfigId $configId)
    {
        $this->configManager = $configManager;
        $this->configId = $configId;
        $this->targetEntityClass = $this->configManager
            ->getProvider('extend')
            ->getConfigById($this->configId)
            ->get('target_entity');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
    }

    /**
     * Sets selected target entity class
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $event->setData($this->targetEntityClass);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'attr'        => array(
                    'class' => 'extend-rel-target-name'
                ),
                'label'       => 'oro.entity_extend.form.target_entity',
                'empty_value' => $this->targetEntityClass ? null : '',
                'read_only'   => (bool) $this->targetEntityClass,
                'choices'     => $this->getEntityChoiceList(
                    $this->configId->getClassName(),
                    $this->configId->getFieldType()
                ),
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
    }

    /**
     * @param string $entityClassName
     * @param string $relationType
     *
     * @return array
     */
    protected function getEntityChoiceList($entityClassName, $relationType)
    {
        /** @var EntityConfigId[] $entityIds */
        $entityIds = $this->targetEntityClass
            ? [$this->configManager->getId('extend', $this->targetEntityClass)]
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
            function (EntityConfigId $configId) {
                $config = $this->configManager->getConfig($configId);

                return $this->isSuitableAsTarget($config, $this->targetEntityClass);
            }
        );

        $choices = [];
        foreach ($entityIds as $entityId) {
            $className = $entityId->getClassName();
            $entityConfig = $this->configManager->getProvider('entity')->getConfig($className);
            $choices[$className] = $entityConfig->get('label');
        }

        return $choices;
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
        return 'genemu_jqueryselect2_choice';
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
