<?php

namespace Oro\Bundle\EntityExtendBundle\Entity\Manager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class AssociationManager
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Returns the list of fields responsible to store associations for the given entity type
     *
     * @param string        $associationOwnerClass The FQCN of the entity that is the owning side of the association
     * @param callable|null $filter                The callback that can be used to filter returned associations.
     *                                             For example you can use it to filter active associations only.
     *                                             Signature:
     *                                             function ($ownerClass, $targetClass, ConfigManager $configManager)
     *                                             The filter should return TRUE if an association between
     *                                             $ownerClass and $targetClass is allowed.
     * @param string        $associationType       The type of the association.
     *                                             For example manyToOne or manyToMany
     *                                             {@see Oro\Bundle\EntityExtendBundle\Extend\RelationType}
     * @param string        $associationKind       The kind of the association.
     *                                             For example 'activity', 'sponsorship' etc
     *                                             Can be NULL for unclassified (default) association
     *
     * @return array [target_entity_class => field_name]
     */
    public function getAssociationTargets(
        $associationOwnerClass,
        $filter,
        $associationType,
        $associationKind = null
    ) {
        $result = [];

        $relations = $this->configManager->getProvider('extend')
            ->getConfig($associationOwnerClass)
            ->get('relation');
        foreach ($relations as $relation) {
            if ($this->isSupportedRelation($relation, $associationType, $associationKind)) {
                $targetClass = $relation['target_entity'];

                if (null === $filter
                    || call_user_func($filter, $associationOwnerClass, $targetClass, $this->configManager)
                ) {
                    /** @var FieldConfigId $fieldConfigId */
                    $fieldConfigId = $relation['field_id'];

                    $result[$targetClass] = $fieldConfigId->getFieldName();
                }
            }
        }

        return $result;
    }

    /**
     * Returns a function which can be used to filter enabled single owner associations
     *
     * @param string $scope     The name of the entity config scope where the association is declared
     * @param string $attribute The name of the entity config attribute which indicates
     *                          whether the association is enabled or not
     *
     * @return callable
     */
    public function getSingleOwnerFilter($scope, $attribute = 'enabled')
    {
        return function ($ownerClass, $targetClass, ConfigManager $configManager) use ($scope, $attribute) {
            return $configManager->getProvider($scope)
                ->getConfig($targetClass)
                ->is($attribute);
        };
    }

    /**
     * Returns a function which can be used to filter enabled multi owner associations
     *
     * @param string $scope     The name of the entity config scope where the association is declared
     * @param string $attribute The name of the entity config attribute which is used to store
     *                          enabled associations
     *
     * @return callable
     */
    public function getMultiOwnerFilter($scope, $attribute)
    {
        return function ($ownerClass, $targetClass, ConfigManager $configManager) use ($scope, $attribute) {
            $ownerClassNames = $configManager->getProvider($scope)
                ->getConfig($targetClass)
                ->get($attribute, false, []);

            return in_array($ownerClass, $ownerClassNames, true);
        };
    }

    /**
     * @param array  $relation
     * @param string $associationType
     * @param string $associationKind
     *
     * @return bool
     */
    protected function isSupportedRelation($relation, $associationType, $associationKind)
    {
        /** @var FieldConfigId|null $fieldConfigId */
        $fieldConfigId = $relation['field_id'];

        return
            $fieldConfigId instanceof FieldConfigId
            && $relation['owner']
            && (
                $fieldConfigId->getFieldType() === $associationType
                || (
                    $associationType === RelationType::MULTIPLE_MANY_TO_ONE
                    && $fieldConfigId->getFieldType() === RelationType::MANY_TO_ONE
                )
            )
            && $fieldConfigId->getFieldName() === ExtendHelper::buildAssociationName(
                $relation['target_entity'],
                $associationKind
            );
    }
}
