<?php
declare(strict_types=1);

namespace Oro\Bundle\ActivityListBundle\Tools;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;

/**
 * Generates PHP code for many-to-many ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND association.
 */
class ActivityListEntityGeneratorExtension extends AbstractAssociationEntityGeneratorExtension
{
    protected ActivityListChainProvider $listProvider;

    public function __construct(ActivityListChainProvider $listProvider)
    {
        $this->listProvider = $listProvider;
    }

    public function supports(array $schema): bool
    {
        return
            $schema['class'] === ActivityListEntityConfigDumperExtension::ENTITY_CLASS
            && parent::supports($schema);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function getAssociationKind(): ?string
    {
        return ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function getAssociationType(): string
    {
        return RelationType::MANY_TO_MANY;
    }
}
