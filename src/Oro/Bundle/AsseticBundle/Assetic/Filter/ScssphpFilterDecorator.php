<?php

namespace Oro\Bundle\AsseticBundle\Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Assetic\Filter\DependencyExtractorInterface;
use Assetic\Filter\ScssphpFilter;

use Leafo\ScssPhp\Exception\CompilerException;

use Psr\Log\LoggerInterface;

class ScssphpFilterDecorator implements DependencyExtractorInterface
{
    /** @var ScssphpFilter */
    private $scssphpFilter;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param ScssphpFilter $scssphpFilter
     */
    public function __construct(ScssphpFilter $scssphpFilter)
    {
        $this->scssphpFilter = $scssphpFilter;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return ScssphpFilterDecorator
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        return $this->scssphpFilter->getChildren($factory, $content, $loadPath);
    }

    /**
     * {@inheritdoc}
     */
    public function filterLoad(AssetInterface $asset)
    {
        try {
            $this->scssphpFilter->filterLoad($asset);
        } catch (CompilerException $e) {
            $this->logger->debug(
                sprintf('Error in method %s::filterLoad() with message: %s', ScssphpFilter::class, $e->getMessage()),
                ['asset' => ['sourcePath' => $asset->getSourcePath()]]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function filterDump(AssetInterface $asset)
    {
        $this->scssphpFilter->filterDump($asset);
    }
}
