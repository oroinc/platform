<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Provider;

use Symfony\Requirements\RequirementCollection;

/**
 * Abstract requirements provider
 */
abstract class AbstractRequirementsProvider implements RequirementsProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getOroRequirements(): ?RequirementCollection
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getPhpIniRequirements(): ?RequirementCollection
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getMandatoryRequirements(): ?RequirementCollection
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getRecommendations(): ?RequirementCollection
    {
        return null;
    }
}
