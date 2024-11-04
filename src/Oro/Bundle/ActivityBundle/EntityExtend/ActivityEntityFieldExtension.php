<?php

namespace Oro\Bundle\ActivityBundle\EntityExtend;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EntityExtendBundle\EntityExtend\AbstractAssociationEntityFieldExtension;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationNameGenerator;

/**
 * Activity extend entity association field extension.
 */
class ActivityEntityFieldExtension extends AbstractAssociationEntityFieldExtension
{
    #[\Override]
    public function isApplicable(EntityFieldProcessTransport $transport): bool
    {
        return $transport->getObject() instanceof ActivityInterface
            && AssociationNameGenerator::extractAssociationKind($transport->getName()) === $this->getRelationKind();
    }

    #[\Override]
    public function getRelationKind(): ?string
    {
        return ActivityScope::ASSOCIATION_KIND;
    }

    #[\Override]
    public function getRelationType(): string
    {
        return RelationType::MANY_TO_MANY;
    }
}
