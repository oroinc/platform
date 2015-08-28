<?php

namespace Oro\Bundle\EntityBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\HttpKernel\KernelInterface;

class LoggingHydratorWarmer extends CacheWarmer
{
    /** @var KernelInterface */
    protected $kernel;

    /**
     * @param array           $hydrators [{hydrator_name} => {hydrator_class}, ...]
     * @param KernelInterface $kernel
     */
    public function __construct(array $hydrators, KernelInterface $kernel)
    {
        $this->hydrators = $hydrators;
        $this->kernel    = $kernel;
    }

    /**
     * {inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->createLoggingHydrators(
            $cacheDir . DIRECTORY_SEPARATOR . 'oro_entities' . DIRECTORY_SEPARATOR . 'OroLoggingHydrator',
            $this->createTwigEnvironment()
        );
    }

    /**
     * {inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * Create Twig_Environment object
     *
     * @return \Twig_Environment
     */
    protected function createTwigEnvironment()
    {
        return new \Twig_Environment(
            new \Twig_Loader_Filesystem(
                $this->kernel->locateResource('@OroEntityBundle/Resources/cache')
            )
        );
    }

    /**
     * Create a proxy class for EmailAddress entity and save it in cache
     *
     * @param string            $cacheDir
     * @param \Twig_Environment $twig
     */
    protected function createLoggingHydrators($cacheDir, \Twig_Environment $twig)
    {
        if (!$this->ensureDirectoryExists($cacheDir)) {
            return;
        }

        foreach ($this->hydrators as $hydrator) {
            $name          = $hydrator['name'];
            $fullClassName = $hydrator['loggingClass'];
            $pos           = strrpos($fullClassName, '\\');
            $className     = substr($fullClassName, $pos + 1);
            $twigParams    = [
                'namespace'       => substr($fullClassName, 0, $pos),
                'className'       => $className,
                'parentClassName' => $hydrator['class'],
                'hydratorName'    => $name
            ];
            $this->writeCacheFile(
                $cacheDir . DIRECTORY_SEPARATOR . $className . '.php',
                $twig->render('LoggingHydrator.php.twig', $twigParams)
            );
        }
    }

    /**
     * @param string $directory
     *
     * @return bool
     */
    protected function ensureDirectoryExists($directory)
    {
        if (is_dir($directory)) {
            return true;
        }

        return @mkdir($directory, 0777, true);
    }
}
