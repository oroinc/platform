<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\EntityExtend;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\EntityExtendBundle\EntityExtend\AbstractAssociationEntityFieldExtension;
use Oro\Bundle\EntityExtendBundle\EntityExtend\EntityFieldProcessTransport;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;

/**
 * Extended Entity Field Processor Extension for attachment associations
 */
class AttachmentEntityFieldExtension extends AbstractAssociationEntityFieldExtension
{
    public function isApplicable(EntityFieldProcessTransport $transport): bool
    {
        return $transport->getClass() === AttachmentScope::ATTACHMENT;
    }

    public function getRelationKind(): ?string
    {
        return null;
    }

    public function getRelationType(): string
    {
        return RelationType::MANY_TO_ONE;
    }
}
