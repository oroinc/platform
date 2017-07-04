<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowListConfiguration;
use Oro\Component\PhpUtils\ArrayUtil;

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

    /**
     * @return string
     */
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

    /**
     * @return string
     */
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

    /**
     * @return array
     */
    public function getReplacements(): array
    {
        return $this->replacements;
    }

    /**
     * @param array $content
     * @return bool
     */
    private function isResourcePresent(array $content): bool
    {
        return isset($content[WorkflowListConfiguration::NODE_WORKFLOWS][$this->getResource()]);
    }

    /**
     * @param array $content
     * @return array
     */
    private function getResourceData(array $content): array
    {
        return (array)$content[WorkflowListConfiguration::NODE_WORKFLOWS][$this->getResource()];
    }

    /**
     * @param array $resourceData
     * @param array $content
     * @return array
     */
    private function mergeConfigs(array $resourceData, array $content): array
    {
        return ArrayUtil::arrayMergeRecursiveDistinct(
            [WorkflowListConfiguration::NODE_WORKFLOWS => [$this->getTarget() => $resourceData]],
            $content
        );
    }

    /**
     * @param array $resourceData
     * @return array
     */
    private function applyReplacements(array $resourceData): array
    {
        foreach ($this->getReplacements() as $path) {
            $resourceData = ArrayUtil::unsetPath($resourceData, explode('.', $path));
        }

        return $resourceData;
    }
}
