<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class MultipleAssociationChoiceType extends AbstractAssociationChoiceType
{
    /** @var array */
    private $owningSideEntities;

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $that    = $this;
        $choices = function (Options $options) use ($that) {
            return $that->getChoices($options['association_class']);
        };

        $resolver->setDefaults(
            [
                'empty_value'       => false,
                'choices'           => $choices,
                'multiple'          => true,
                'expanded'          => true,
                'association_class' => null, // the group name for owning side entities
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $disabledValues = $this->getReadOnlyValues($options);
        /** @var FormView $choiceView */
        foreach ($view->children as $choiceView) {
            if ((isset($view->vars['disabled']) && $view->vars['disabled'])
                || (!empty($disabledValues) && in_array($choiceView->vars['value'], $disabledValues))
            ) {
                $choiceView->vars['disabled'] = true;
            }
        }
    }

    /**
     * @param string $groupName
     * @return array
     */
    protected function getChoices($groupName)
    {
        $this->ensureOwningSideEntitiesLoaded($groupName);

        $choices              = [];
        $entityConfigProvider = $this->configManager->getProvider('entity');
        foreach ($this->owningSideEntities as $className) {
            $choices[$className] = $entityConfigProvider->getConfig($className)->get('plural_label');
        }

        return $choices;
    }

    /**
     * {@inheritdoc}
     */
    protected function isSchemaUpdateRequired($newVal, $oldVal)
    {
        return !empty($newVal) && $newVal != (array)$oldVal;
    }

    /**
     * {@inheritdoc}
     */
    protected function isReadOnly($options)
    {
        $this->ensureOwningSideEntitiesLoaded($options['association_class']);

        /** @var EntityConfigId $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();

        // disable for owning side entities
        if (!empty($className) && in_array($className, $this->owningSideEntities)) {
            return true;
        };

        return parent::isReadOnly($options);
    }

    /**
     * Gets the list of values which state cannot be changed
     *
     * @param array $options
     *
     * @return string[]
     */
    protected function getReadOnlyValues(array $options)
    {
        $result = [];

        /** @var EntityConfigId $configId */
        $configId       = $options['config_id'];
        $className      = $configId->getClassName();
        if (!empty($className)) {
            $configProvider = $this->configManager->getProvider($configId->getScope());
            if ($configProvider->hasConfig($className)) {
                $immutable = $configProvider->getConfig($className)->get('immutable');
                if (is_array($immutable) && !empty($immutable)) {
                    $result = $immutable;
                }
            }
        }

        return $result;
    }

    /**
     * Makes sure that the owning side entities are loaded
     *
     * @param string $groupName
     */
    protected function ensureOwningSideEntitiesLoaded($groupName)
    {
        if (null === $this->owningSideEntities) {
            $this->owningSideEntities = $this->loadOwningSideEntities($groupName);
        }
    }

    /**
     * Loads the list of owning side entities
     *
     * @param $groupName
     * @return string[]
     */
    protected function loadOwningSideEntities($groupName)
    {
        $result  = [];
        $configs = $this->configManager->getProvider('grouping')->getConfigs();
        foreach ($configs as $config) {
            $groups = $config->get('groups');
            if (!empty($groups) && in_array($groupName, $groups)) {
                $result[] = $config->getId()->getClassName();
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_entity_extend_multiple_association_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
