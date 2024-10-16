<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Provider;

use Symfony\Requirements\RequirementCollection;

/**
 * Abstract requirements provider
 */
abstract class AbstractRequirementsProvider implements RequirementsProviderInterface
{
    #[\Override]
    public function getOroRequirements(): ?RequirementCollection
    {
        return null;
    }

    #[\Override]
    public function getPhpIniRequirements(): ?RequirementCollection
    {
        return null;
    }

    #[\Override]
    public function getMandatoryRequirements(): ?RequirementCollection
    {
        return null;
    }

    #[\Override]
    public function getRecommendations(): ?RequirementCollection
    {
        return null;
    }
}
