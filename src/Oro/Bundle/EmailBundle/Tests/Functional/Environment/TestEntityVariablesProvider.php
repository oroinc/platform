<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Environment;

use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

class TestEntityVariablesProvider implements EntityVariablesProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVariableDefinitions(): array
    {
        return [
            Organization::class  => [
                'computedArray'  => [
                    'type'  => 'array',
                    'label' => 'Computed Array'
                ],
                'computedObject' => [
                    'type'  => RelationType::TO_ONE,
                    'label' => 'Computed Object'
                ]
            ],
            EmailTemplate::class => [
                'computedOrg' => [
                    'type'                => RelationType::TO_ONE,
                    'label'               => 'Computed Organization',
                    'related_entity_name' => Organization::class
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableGetters(): array
    {
        return [
            EmailTemplate::class => [
                'subject' => 'getSubject'
            ],
            Item::class          => [
                'owner' => null
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableProcessors(string $entityClass): array
    {
        if (Organization::class === $entityClass) {
            return [
                'computedArray'  => [
                    'processor' => 'tests.processor',
                    'type'      => 'array'
                ],
                'computedObject' => [
                    'processor' => 'tests.processor',
                    'type'      => 'object'
                ]
            ];
        }

        if (EmailTemplate::class === $entityClass) {
            return [
                'computedOrg' => [
                    'processor' => 'tests.processor',
                    'type'      => 'object_organization'
                ]
            ];
        }

        return [];
    }
}
