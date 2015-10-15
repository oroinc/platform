<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Util\AssociationTypeHelper;

/**
 * The abstract class for form types are used to work with entity config attributes
 * related to associations.
 */
abstract class AbstractAssociationType extends AbstractConfigType
{
    /** @var AssociationTypeHelper */
    protected $typeHelper;

    /**
     * @param AssociationTypeHelper $typeHelper
     * @param ConfigManager         $configManager
     */
    public function __construct(AssociationTypeHelper $typeHelper, ConfigManager $configManager)
    {
        parent::__construct($typeHelper, $configManager);
        $this->typeHelper = $typeHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

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
    protected function isReadOnly($options)
    {
        /** @var EntityConfigId $configId */
        $configId  = $options['config_id'];
        $className = $configId->getClassName();

        if (!empty($className)) {
            if ($this->typeHelper->isAssociationOwningSideEntity($className, $options['association_class'])) {
                return true;
            }
            if ($this->typeHelper->isDictionary($className)
                && !$this->typeHelper->isSupportActivityEnabled($className)) {
                return true;
            }
        }

        return parent::isReadOnly($options);
    }
}
