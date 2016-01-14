<?php

namespace Oro\Bundle\ActivityBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\MultipleAssociationChoiceType as BaseMultipleAssociationChoiceType;

class MultipleAssociationChoiceType extends BaseMultipleAssociationChoiceType
{
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
                    return $this->getChoices($options);
                },
                'multiple'    => true,
                'expanded'    => true
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_activity_multiple_association_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_entity_extend_multiple_association_choice';
    }

    /**
     * @param Options $options
     *
     * @return array
     */
    protected function getChoices($options)
    {
        $groupName = $options['association_class'];
        /** @var EntityConfigId $configId */
        $configId  = $options['config_id'];
        $targetClassName = $configId->getClassName();

        $choices = [];
        $entityConfigProvider = $this->configManager->getProvider('entity');
        $owningSideEntities = $this->typeHelper->getOwningSideEntities($groupName);
        foreach ($owningSideEntities as $className) {
            if ($targetClassName !== $className) {
                $choices[$className] = $entityConfigProvider->getConfig($className)->get('plural_label');
            }
        }

        return $choices;
    }
}
