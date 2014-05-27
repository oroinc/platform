<?php

namespace Oro\Bundle\NoteBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Doctrine\Common\Inflector\Inflector;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 *  Form extension to communicate with ConfigScopeType
 *  will fire only on attribute "enabled" in scope "note"
 *
 *  The goal of this extension is to check if attribute is enabled.
 *  In this case additional checking for relation existing will be applied.
 *  And if relation is not exists yet, field stays editable but entity will be marked as "Required Update".
 *  If relation already exists ("Schema Update" action was applied) field will be Read-Only.
 *
 */
class NoteExtension extends AbstractTypeExtension
{
    const SCOPE                = 'note';
    const ATTR_ENABLED         = 'enabled';
    const NOTE_RELATION_PREFIX = 'assoc_note_';
    const NOTE_ENTITY_CLASS    = 'Oro\Bundle\NoteBundle\Entity\Note';

    /** @var ConfigProvider */
    protected $noteConfigProvider;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->noteConfigProvider   = $configManager->getProvider(self::SCOPE);
        $this->extendConfigProvider = $configManager->getProvider('extend');
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($options) {
                $form = $event->getForm();
                if ($this->isApplicable($form->getName(), $options)
                    && $form->getData() == true
                ) {
                    /** @var ConfigIdInterface $entityConfigId */
                    $entityConfigId = $options['config_id'];
                    $entityConfig = $this->extendConfigProvider->getConfig($entityConfigId->getClassName());
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
     * @inheritdoc
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($this->isApplicable($form->getName(), $options)) {
            /** @var ConfigIdInterface $entityConfigId */
            $entityConfigId = $options['config_id'];
            $noteConfig     = $this->noteConfigProvider->getConfigById($entityConfigId);

            /**
             * Disable field on editAction if it enabled and relation exists
             *      OR it's Note entity
             */
            if ($entityConfigId->getClassName() == self::NOTE_ENTITY_CLASS
                || (
                    $noteConfig->get(self::ATTR_ENABLED) == true
                    && $this->isNoteRelationExists($entityConfigId)
                )
            ) {
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
            $propertyName == self::ATTR_ENABLED
            && isset($options['config_id'])
            && $options['config_id'] instanceof EntityConfigId
            && $options['config_id']->getScope() == self::SCOPE;
    }

    /**
     * Check for note relation existence
     *
     * @param ConfigIdInterface $configId
     *
     * @return bool
     */
    protected function isNoteRelationExists(ConfigIdInterface $configId)
    {
        $config    = $this->extendConfigProvider->getConfigById($configId);
        $relations = $config->get('relation');
        if ($relations) {
            /**
             * assoc_note_[entity name]
             * e.g. "assoc_note_user", "assoc_note_calendar_event"
             */
            $relationFieldName =
                self::NOTE_RELATION_PREFIX .
                Inflector::tableize(ExtendHelper::getShortClassName($configId->getClassName()));

            /**
             * e.g. "manyToOne|Oro\Bundle\NoteBundle\Entity\Note|Oro\Bundle\UserBundle\Entity\User|assoc_note_user"
             */
            $relationKey = ExtendHelper::buildRelationKey(
                self::NOTE_ENTITY_CLASS,
                $relationFieldName,
                'manyToOne',
                $configId->getClassName()
            );

            if (isset($relations[$relationKey])) {
                $relation = $relations[$relationKey];
                if ($relation['assign'] == false
                    && $relation['owner'] == false
                    && $relation['target_field_id']
                    && $this->extendConfigProvider->getConfig($relation['target_field_id']->getClassName())
                        ->get('relation')[$relationKey]['assign'] == true
                ) {
                    return true;
                }
            }
        }

        return false;
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
     * @inheritdoc
     */
    public function getExtendedType()
    {
        return 'note_choice';
    }
}
