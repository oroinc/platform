<?php
declare(strict_types=1);

namespace Oro\Bundle\ActivityListBundle\EntityExtend;

use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\EntityExtend\AbstractAssociationEntityFieldExtension;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Extended Entity Field Processor Extension for activity list associations
 */
class ActivityListEntityFieldExtension extends AbstractAssociationEntityFieldExtension
{
    protected function isApplicable(EntityFieldProcessTransport $transport): bool
    {
        return $transport->getClass() === ActivityListEntityConfigDumperExtension::ENTITY_CLASS;
    }

    protected function getRelationKind(): ?string
    {
        return ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND;
    }

    protected function getRelationType(): string
    {
        return RelationType::MANY_TO_MANY;
    }
}
