<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Provider;

use Symfony\Requirements\RequirementCollection;

/**
 * Interface for requirements providers
 */
interface RequirementsProviderInterface
{
    /**
     * Mandatory requirements category
     */
    public function getMandatoryRequirements(): ?RequirementCollection;

    /**
     * PHP config requirements category
     */
    public function getPhpIniRequirements(): ?RequirementCollection;

    /**
     * Oro requirements category
     */
    public function getOroRequirements(): ?RequirementCollection;

    /**
     * Recommendations category
     */
    public function getRecommendations(): ?RequirementCollection;
}
