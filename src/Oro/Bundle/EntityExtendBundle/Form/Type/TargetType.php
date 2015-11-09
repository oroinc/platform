<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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

    public function __construct(ConfigManager $configManager, $configId)
    {
        $this->configManager = $configManager;
        $this->configId = $configId;
        $this->targetEntity = $this->configManager
            ->getProvider('extend')
            ->getConfigById($this->configId)
            ->get('target_entity');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
    }

    public function preSetData(FormEvent $event)
    {
        $event->setData($this->targetEntity);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'attr'        => array(
                    'class' => 'extend-rel-target-name'
                ),
                'label'       => 'oro.entity_extend.form.target_entity',
                'empty_value' => $this->targetEntity ? null : '',
                'read_only'   => (bool) $this->targetEntity,
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

    protected function getEntityChoiceList($entityClassName, $relationType)
    {
        $choices       = [];
        $extendEntityConfig = $this->configManager->getProvider('extend');

        /** @var EntityConfigId[] $entityIds */
        $entityIds = $this->targetEntity
            ? [$extendEntityConfig->getId($this->targetEntity)]
            : $extendEntityConfig->getIds();

        if (in_array($relationType, [RelationTypeBase::ONE_TO_MANY, RelationTypeBase::MANY_TO_MANY], true)) {
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

                return
                    !$config->is('state', ExtendScope::STATE_NEW)
                    && (
                        $this->targetEntity
                        || !$config->is('is_deleted')
                    );
            }
        );

        foreach ($entityIds as $entityId) {
            $className = $entityId->getClassName();
            if ($className !== $entityClassName) {
                $entityConfig        = $this->configManager->getProvider('entity')->getConfig($className);
                $choices[$className] = $entityConfig->get('label');
            }
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
        return 'oro_entity_target_type';
    }
}
