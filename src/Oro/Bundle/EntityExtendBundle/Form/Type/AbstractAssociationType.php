<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The abstract class for form types are used to work with entity config attributes
 * related to associations.
 */
abstract class AbstractAssociationType extends AbstractConfigType
{
    protected AssociationTypeHelper $associationTypeHelper;

    public function __construct(AssociationTypeHelper $associationTypeHelper, ConfigManager $configManager)
    {
        parent::__construct($associationTypeHelper, $configManager);
        $this->associationTypeHelper = $associationTypeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                // specifies the owning side entity, can be:
                // - full class name or entity name for single association
                // - a group name for multiple association
                // it is supposed that the group name should not contain \ and : characters
                'association_class' => null
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function isReadOnly(Options $options)
    {
        /** @var EntityConfigId $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();

        if ($className && $this->associationTypeHelper->isDictionary($className)) {
            return true;
        }

        return parent::isReadOnly($options);
    }
}
