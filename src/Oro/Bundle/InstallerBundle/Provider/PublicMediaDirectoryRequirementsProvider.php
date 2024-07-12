<?php

namespace Oro\Bundle\InstallerBundle\Provider;

use Symfony\Requirements\RequirementCollection;

/**
 * Requirements provider for the public/media directory
 */
class PublicMediaDirectoryRequirementsProvider extends AbstractRequirementsProvider
{
    public function __construct(
        protected string $projectDirectory,
    ) {
    }

    #[\Override]
    public function getOroRequirements(): ?RequirementCollection
    {
        $collection = new RequirementCollection();
        $this->addPathWritableRequirement($collection, 'public/media');

        return $collection;
    }

    protected function addPathWritableRequirement(RequirementCollection $collection, string $path): void
    {
        $fullPath = $this->projectDirectory . '/' . $path;
        $pathType = is_file($fullPath) ? 'file' : 'directory';

        $collection->addRequirement(
            is_writable($fullPath),
            $path . ' directory must be writable',
            'Change the permissions of the "<strong>' . $path . '</strong>" ' . $pathType . ' so' .
            ' that the web server can write into it.'
        );
    }
}
