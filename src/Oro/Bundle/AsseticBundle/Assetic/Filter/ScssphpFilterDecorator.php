<?php

namespace Oro\Bundle\AsseticBundle\Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Assetic\Filter\DependencyExtractorInterface;
use Assetic\Filter\ScssphpFilter;

class ScssphpFilterDecorator implements DependencyExtractorInterface
{
    /** @var ScssphpFilter */
    private $scssphpFilter;

    /** @var boolean */
    private $skipFilterLoading = false;

    /**
     * @param ScssphpFilter $scssphpFilter
     */
    public function __construct(ScssphpFilter $scssphpFilter)
    {
        $this->scssphpFilter = $scssphpFilter;
    }

    /**
     * @param bool $enable
     */
    public function enableCompass($enable = true)
    {
        $this->scssphpFilter->enableCompass($enable);
    }

    /**
     * @return boolean
     */
    public function isCompassEnabled()
    {
        return $this->scssphpFilter->isCompassEnabled();
    }

    /**
     * @param string $formatter
     */
    public function setFormatter($formatter)
    {
        $this->scssphpFilter->setFormatter($formatter);
    }

    /**
     * @param array $variables
     */
    public function setVariables(array $variables)
    {
        $this->scssphpFilter->setVariables($variables);
    }

    /**
     * @param mixed $variable
     */
    public function addVariable($variable)
    {
        $this->scssphpFilter->addVariable($variable);
    }

    /**
     * @param array $paths
     */
    public function setImportPaths(array $paths)
    {
        $this->scssphpFilter->setImportPaths($paths);
    }

    /**
     * @param string $path
     */
    public function addImportPath($path)
    {
        $this->scssphpFilter->addImportPath($path);
    }

    /**
     * @param string $name
     * @param callable $callable
     */
    public function registerFunction($name, $callable)
    {
        $this->scssphpFilter->registerFunction($name, $callable);
    }

    /**
     * {@inheritdoc}
     */
    public function filterLoad(AssetInterface $asset)
    {
        if (!$this->skipFilterLoading) {
            $this->scssphpFilter->filterLoad($asset);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function filterDump(AssetInterface $asset)
    {
        $this->scssphpFilter->filterDump($asset);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        $this->skipFilterLoading = true;
        $children = $this->scssphpFilter->getChildren($factory, $content, $loadPath);
        $this->skipFilterLoading = false;

        return $children;
    }
}
