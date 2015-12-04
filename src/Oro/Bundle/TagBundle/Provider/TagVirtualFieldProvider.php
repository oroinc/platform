<?php

namespace Oro\Bundle\TagBundle\Provider;

use Oro\Bundle\EntityBundle\EntityConfig\GroupingScope;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;

class TagVirtualFieldProvider implements VirtualFieldProviderInterface
{
    const TAG_FIELD_NAME = 'name';

    /**
     * {@inheritdoc}
     */
    public function isVirtualField($className, $fieldName)
    {
        return
            $className === 'Oro\Bundle\TagBundle\Entity\Tag' &&
            $fieldName === self::TAG_FIELD_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFieldQuery($className, $fieldName)
    {
        return [
            'select' => [
                'expr'                => 'entity.name',
                'label'               => 'oro.tag.entity_label',
                'return_type'         => GroupingScope::GROUP_DICTIONARY,
                'related_entity_name' => $className,
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualFields($className)
    {
        if ($className === 'Oro\Bundle\TagBundle\Entity\Tag') {
            return [self::TAG_FIELD_NAME];
        }

        return [];
    }
}
