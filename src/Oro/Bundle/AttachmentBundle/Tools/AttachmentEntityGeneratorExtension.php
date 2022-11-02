<?php
declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tools;

use Oro\Bundle\AttachmentBundle\EntityConfig\AttachmentScope;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;

/**
 * Generates PHP code for many-to-one attachment association.
 */
class AttachmentEntityGeneratorExtension extends AbstractAssociationEntityGeneratorExtension
{
    public function supports(array $schema): bool
    {
        return
            $schema['class'] === AttachmentScope::ATTACHMENT
            && parent::supports($schema);
    }
}
