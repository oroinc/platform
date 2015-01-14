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
use Oro\Bundle\FormBundle\Form\Type\ChoiceListItem;

class TargetType extends AbstractType
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var FieldConfigId
     */
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
        $choices       = array();
        $extendEntityConfig = $this->configManager->getProvider('extend');

        /** @var EntityConfigId[] $entityIds */
        $entityIds = $this->targetEntity
            ? array($extendEntityConfig->getId($this->targetEntity))
            : $extendEntityConfig->getIds();

        if (in_array($relationType, array(RelationTypeBase::ONE_TO_MANY, RelationTypeBase::MANY_TO_MANY))) {
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

                return $config->is('is_extend', false) || !$config->is('state', ExtendScope::STATE_NEW);
            }
        );

        foreach ($entityIds as $entityId) {
            $className = $entityId->getClassName();
            if ($className != $entityClassName) {
                $entityConfig        = $this->configManager->getProvider('entity')->getConfig($className);
                $choices[$className] = new ChoiceListItem(
                    $entityConfig->get('label'),
                    array(
                        'data-icon' => $entityConfig->get('icon')
                    )
                );
            }
        }

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
        return 'oro_entity_target_type';
    }
}
