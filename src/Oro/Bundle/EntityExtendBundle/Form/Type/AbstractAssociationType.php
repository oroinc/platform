<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

/**
 * The abstract class for form types are used to work with entity config attributes
 * related to associations.
 */
abstract class AbstractAssociationType extends AbstractConfigType
{
    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var array */
    protected $owningSideEntities;

    /**
     * @param ConfigManager       $configManager
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(ConfigManager $configManager, EntityClassResolver $entityClassResolver)
    {
        parent::__construct($configManager);
        $this->entityClassResolver = $entityClassResolver;
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

        // disable for owning side entity
        $associationClass = $options['association_class'];
        if (strpos($associationClass, ':') !== false || strpos($associationClass, '\\') !== false) {
            // the association class is full class name or entity name
            if ($className === $this->entityClassResolver->getEntityClass($associationClass)) {
                return true;
            }
        } else {
            // the association class is a group name
            $this->ensureOwningSideEntitiesLoaded($associationClass);
            if (!empty($className) && in_array($className, $this->owningSideEntities)) {
                return true;
            };
        }

        if (!empty($className)) {
            // disable for dictionary entities
            $groupingConfigProvider = $this->configManager->getProvider('grouping');
            if ($groupingConfigProvider->hasConfig($className)) {
                $groups = $groupingConfigProvider->getConfig($className)->get('groups');
                if (!empty($groups) && in_array(GroupingScope::GROUP_DICTIONARY, $groups)) {
                    return true;
                }
            }
        }

        return parent::isReadOnly($options);
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
}
