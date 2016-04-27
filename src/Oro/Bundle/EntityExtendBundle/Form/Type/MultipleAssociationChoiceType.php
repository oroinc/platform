<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

class MultipleAssociationChoiceType extends AbstractAssociationType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::SUBMIT, array($this, 'submit'));
    }

    /**
     * SUBMIT event handler
     *
     * @param FormEvent $event
     */
    public function submit(FormEvent $event)
    {
        $form    = $event->getForm();
        $options = $form->getConfig()->getOptions();
        /** @var ConfigIdInterface $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();

        if (empty($className)) {
            return;
        }

        $immutable = $this->typeHelper->getImmutable($configId->getScope(), $className);
        if (is_array($immutable) && !empty($immutable)) {
            // set new values, but keep existing immutable values
            $existingValues = $this->configManager->getConfig($configId)->get($form->getName());
            if ($existingValues === null) {
                $existingValues = [];
            }
            $event->setData(array_merge(array_intersect($existingValues, $immutable), $event->getData()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(
            [
                'empty_value' => false,
                'choices'     => function (Options $options) {
                    return $this->getChoices($options['association_class']);
                },
                'multiple'    => true,
                'expanded'    => true,
                'schema_update_required' => function ($newVal, $oldVal) {
                    if (!is_array($oldVal)) {
                        $oldVal = (array)$oldVal;
                    }

                    sort($newVal, SORT_STRING);
                    sort($oldVal, SORT_STRING);

                    return $newVal != $oldVal;
                },
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

    /**
     * @param string $groupName
     *
     * @return array
     */
    protected function getChoices($groupName)
    {
        $choices              = [];
        $entityConfigProvider = $this->configManager->getProvider('entity');
        $owningSideEntities   = $this->typeHelper->getOwningSideEntities($groupName);
        foreach ($owningSideEntities as $className) {
            $choices[$className] = $entityConfigProvider->getConfig($className)->get('plural_label');
        }

        return $choices;
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
        /** @var EntityConfigId $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();

        if (!empty($className)) {
            $immutable = $this->typeHelper->getImmutable($configId->getScope(), $className);
            if (is_array($immutable)) {
                return $immutable;
            }
        }

        return [];
    }
}
