<?php
declare(strict_types=1);

namespace Oro\Bundle\ActivityBundle\Tools;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;
use Oro\Component\PhpUtils\ClassGenerator;

/**
 * Generates PHP code for ActivityScope::ASSOCIATION_KIND many-to-many association.
 */
class ActivityEntityGeneratorExtension extends AbstractAssociationEntityGeneratorExtension
{
    protected ConfigProvider $groupingConfigProvider;

    public function __construct(ConfigProvider $groupingConfigProvider)
    {
        $this->groupingConfigProvider = $groupingConfigProvider;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function supports(array $schema): bool
    {
        if (!$this->groupingConfigProvider->hasConfig($schema['class'])) {
            return false;
        }

        $groups = $this->groupingConfigProvider->getConfig($schema['class'])->get('groups');

        return
            !empty($groups)
            && \in_array(ActivityScope::GROUP_ACTIVITY, $groups);
    }

    public function generate(array $schema, ClassGenerator $class): void
    {
        $class->addImplement(ActivityInterface::class);

        parent::generate($schema, $class);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function getAssociationKind(): ?string
    {
        return ActivityScope::ASSOCIATION_KIND;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function getAssociationType(): string
    {
        return RelationType::MANY_TO_MANY;
    }
}
