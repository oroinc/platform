<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Adds the Email activity relation fields to the EmailUser entity.
 */
class EmailUserVirtualRelationsProvider implements VirtualRelationProviderInterface
{
    protected AssociationManager $associationManager;
    private ConfigProvider $configProvider;

    public function __construct(AssociationManager $associationManager, ConfigProvider $configProvider)
    {
        $this->associationManager = $associationManager;
        $this->configProvider = $configProvider;
    }

    #[\Override]
    public function isVirtualRelation($className, $fieldName)
    {
        return
            is_a($className, EmailUser::class, true)
            && \in_array($fieldName, array_values($this->getTargets()), true);
    }

    #[\Override]
    public function getVirtualRelationQuery($className, $fieldName)
    {
        return $this->getQueryPart($fieldName);
    }

    #[\Override]
    public function getVirtualRelations($className)
    {
        if (!is_a($className, EmailUser::class, true)) {
            return [];
        }

        $targets = $this->getTargets();
        $result = [];
        foreach ($targets as $targetClassName => $fieldName) {
            $result[$fieldName] = [
                'label' => $this->configProvider->getConfig($targetClassName)->get('label'),
                'relation_type' => RelationType::MANY_TO_MANY,
                'related_entity_name' => $targetClassName,
                'target_join_alias' => $fieldName,
                'query' => $this->getQueryPart($fieldName)
            ];
        }

        return $result;
    }

    #[\Override]
    public function getTargetJoinAlias($className, $fieldName, $selectFieldName = null)
    {
        return $fieldName;
    }

    private function getQueryPart(string $fieldName): array
    {
        $alias = $fieldName . '_al';

        return [
            'join' => [
                'left' => [
                    [
                        'join' => 'entity.email',
                        'alias' => $alias,
                        'conditionType' => Join::WITH
                    ],
                    [
                        'join' => $alias . '.' . $fieldName,
                        'alias' => $fieldName,
                        'conditionType' => Join::WITH
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array ['className' => 'fieldName', ...]
     */
    private function getTargets(): array
    {
        return $this->associationManager->getAssociationTargets(
            Email::class,
            null,
            RelationType::MANY_TO_MANY,
            ActivityScope::ASSOCIATION_KIND
        );
    }
}
