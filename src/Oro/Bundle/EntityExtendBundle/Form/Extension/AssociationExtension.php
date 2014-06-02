<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\AssociationChoiceType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 *  Form extension to communicate with ConfigScopeType
 *  will fire only on an attribute which is used to enabled/disable an association
 *
 *  The goal of this extension is to check if attribute is enabled.
 *  In this case additional checking for relation existing will be applied.
 *  And if relation is not exists yet, field stays editable but entity will be marked as "Required Update".
 *  If relation already exists ("Schema Update" action was applied) field will be Read-Only.
 */
class AssociationExtension extends AbstractTypeExtension
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @param ConfigManager       $configManager
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(ConfigManager $configManager, EntityClassResolver $entityClassResolver)
    {
        $this->configManager        = $configManager;
        $this->entityClassResolver  = $entityClassResolver;
        $this->extendConfigProvider = $configManager->getProvider('extend');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($options) {
                $form = $event->getForm();
                if ($this->isApplicable($form->getName(), $options) && $form->getData() == true) {
                    /** @var EntityConfigId $configId */
                    $configId     = $options['config_id'];
                    $entityConfig = $this->extendConfigProvider->getConfig($configId->getClassName());
                    if ($entityConfig->is('state', ExtendScope::STATE_ACTIVE)) {
                        $entityConfig->set('state', ExtendScope::STATE_UPDATED);

                        $this->extendConfigProvider->persist($entityConfig);
                        $this->extendConfigProvider->flush();
                    } else {
                        /**
                         * TODO: check if has other changes
                         *      if NO -> revert state to "Active"
                         * depends on EntityExtendBundle/Form/Extension/ExtendEntityExtension.php
                         *      method: hasActiveFields
                         */
                    }
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($this->isApplicable($form->getName(), $options)) {
            /** @var EntityConfigId $configId */
            $configId               = $options['config_id'];
            $className              = $configId->getClassName();
            $entityConfig           = $this->configManager
                ->getProvider($options['entity_config_scope'])
                ->getConfig($className);
            $owningEntityClassName = $this->entityClassResolver->getEntityClass($options['entity_class']);

            /**
             * Disable the association choice element if the association already exists
             *      OR the editing entity is the owning side of association
             */
            if ($className === $owningEntityClassName
                || (
                    $entityConfig->is($options['entity_config_attribute_name'])
                    && $this->isAssociationExist($className, $owningEntityClassName)
                )
            ) {
                // @todo: form type should be disabled correctly
                $options['disabled'] = true;
                $this->appendClassAttr($view->vars, 'disabled-choice');
            }
        }
    }

    /**
     * @param string $propertyName
     * @param array  $options
     *
     * @return bool
     */
    protected function isApplicable($propertyName, $options)
    {
        return
            $propertyName == $options['entity_config_attribute_name']
            && isset($options['config_id'])
            && $options['config_id'] instanceof EntityConfigId
            && $options['config_id']->getScope() == $options['entity_config_scope'];
    }

    /**
     * Checks if the association between the target entity and the owning entity exists
     *
     * @param string $targetEntityClassName
     * @param string $owningEntityClassName
     *
     * @return bool
     */
    protected function isAssociationExist($targetEntityClassName, $owningEntityClassName)
    {
        $result    = false;
        $config    = $this->extendConfigProvider->getConfig($targetEntityClassName);
        $relations = $config->get('relation');
        if ($relations) {
            $relationFieldName = ExtendHelper::buildAssociationName($targetEntityClassName);
            /**
             * e.g. "manyToOne|Acme\Bundle\AcmeBundle\Entity\Activity|Oro\Bundle\UserBundle\Entity\User|user"
             */
            $relationKey = ExtendHelper::buildRelationKey(
                $owningEntityClassName,
                $relationFieldName,
                'manyToOne',
                $targetEntityClassName
            );

            if (isset($relations[$relationKey])) {
                $relation = $relations[$relationKey];
                if ($relation['assign'] == false
                    && $relation['owner'] == false
                    && $relation['target_field_id']
                    && $this->extendConfigProvider->getConfig($relation['target_field_id']->getClassName())
                        ->get('relation')[$relationKey]['assign'] == true
                ) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    protected function appendClassAttr(array &$vars, $cssClass)
    {
        if (isset($vars['attr']['class'])) {
            $vars['attr']['class'] .= ' ' . $cssClass;
        } else {
            $vars['attr']['class'] = $cssClass;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return AssociationChoiceType::NAME;
    }
}
