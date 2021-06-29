<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Provider;

use Oro\Bundle\InstallerBundle\Symfony\Requirements\RequirementCollection;

/**
 * Interface for requirements providers
 */
interface RequirementsProviderInterface
{
    /**
     * Mandatory requirements category
     *
     * @return RequirementCollection|null
     */
    public function getMandatoryRequirements(): ?RequirementCollection;

    /**
     * PHP config requirements category
     *
     * @return RequirementCollection|null
     */
    public function getPhpIniRequirements(): ?RequirementCollection;

    /**
     * Oro requirements category
     *
     * @return RequirementCollection|null
     */
    public function getOroRequirements(): ?RequirementCollection;

    /**
     * Recommendations category
     *
     * @return RequirementCollection|null
     */
    public function getRecommendations(): ?RequirementCollection;
}
