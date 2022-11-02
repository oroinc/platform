<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowListConfiguration;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Contains common methods for import processors.
 */
trait WorkflowImportTrait
{
    /** @var string */
    protected $resource;

    /** @var string */
    protected $target;

    /** @var array */
    protected $replacements;

    /**
     * @param string $resource
     * @return $this
     */
    public function setResource(string $resource)
    {
        $this->resource = $resource;

        return $this;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * @param string $target
     * @return $this
     */
    public function setTarget(string $target)
    {
        $this->target = $target;

        return $this;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @param array $replacements
     * @return $this
     */
    public function setReplacements(array $replacements)
    {
        $this->replacements = $replacements;

        return $this;
    }

    public function getReplacements(): array
    {
        return $this->replacements;
    }

    private function isResourcePresent(array $content): bool
    {
        return isset($content[WorkflowListConfiguration::NODE_WORKFLOWS][$this->getResource()]);
    }

    private function getResourceData(array $content): array
    {
        return (array)$content[WorkflowListConfiguration::NODE_WORKFLOWS][$this->getResource()];
    }

    private function mergeImports(array $content, array $importedContent): array
    {
        return ArrayUtil::arrayMergeRecursiveDistinct(
            $content,
            [WorkflowListConfiguration::NODE_WORKFLOWS => [$this->getTarget() => $importedContent]]
        );
    }

    private function applyReplacements(array $resourceData): array
    {
        foreach ($this->getReplacements() as $path) {
            $resourceData = ArrayUtil::unsetPath($resourceData, explode('.', $path));
        }

        return $resourceData;
    }
}
