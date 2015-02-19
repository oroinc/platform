<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Psr\Log\NullLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

use Oro\Bundle\LayoutBundle\Theme\ThemeManager;
use Oro\Component\Layout\Extension\AbstractExtension;
use Oro\Bundle\LayoutBundle\Layout\Loader\FileLoaderInterface;

class ThemeExtension extends AbstractExtension implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array */
    protected $resources;

    /** @var ThemeManager */
    protected $manager;

    /** @var FileLoaderInterface[] */
    protected $loaders = [];

    /**
     * @param array                 $resources
     * @param ThemeManager          $manager
     * @param FileLoaderInterface[] $loaders
     */
    public function __construct(array $resources, ThemeManager $manager, $loaders = [])
    {
        $this->resources = $resources;
        $this->manager   = $manager;
        $this->loaders   = $loaders;
        $this->setLogger(new NullLogger());
    }

    /**
     * @param FileLoaderInterface $loader
     */
    public function addLoader(FileLoaderInterface $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdates()
    {
        $updates = $skipped = [];
        $theme   = $this->manager->getTheme();

        $resources = isset($this->resources[$theme->getDirectory()]) ? $this->resources[$theme->getDirectory()] : [];
        while ($resource = array_pop($resources)) {
            $found = false;
            foreach ($this->loaders as $loader) {
                if ($loader->supports($resource)) {
                    $updates[] = $loader->load($resource);

                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $skipped[] = $resource;
            }
        }

        array_walk($skipped, [$this, 'logUnknownResource']);

        return ['root' => $updates];
    }

    /**
     * @param string $resource
     */
    protected function logUnknownResource($resource)
    {
        $this->logger->notice(sprintf('Skipping resource "%s" because loader for it not found', $resource));
    }
}
