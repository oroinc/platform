<?php

namespace Oro\Bundle\EntityBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;

/**
 * Creates classes for logging hydrators.
 */
class LoggingHydratorWarmer extends CacheWarmer
{
    /** @var array [hydrator name => hydrator class, ...] */
    private $hydrators;

    /**
     * @param array $hydrators [hydrator name => hydrator class, ...]
     */
    public function __construct(array $hydrators)
    {
        $this->hydrators = $hydrators;
    }

    /**
     * {inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->createLoggingHydrators(
            $cacheDir . DIRECTORY_SEPARATOR . 'oro_entities' . DIRECTORY_SEPARATOR . 'OroLoggingHydrator'
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
     * Create a proxy class for EmailAddress entity and save it in cache
     *
     * @param string $cacheDir
     */
    protected function createLoggingHydrators($cacheDir)
    {
        if (!$this->ensureDirectoryExists($cacheDir)) {
            return;
        }

        foreach ($this->hydrators as $hydrator) {
            $hydratorName    = $hydrator['name'];
            $fullClassName   = $hydrator['loggingClass'];
            $pos             = strrpos($fullClassName, '\\');
            $namespace       = substr($fullClassName, 0, $pos);
            $className       = substr($fullClassName, $pos + 1);
            $parentClassName = $hydrator['class'];

            $countString = 'null === $result ? 0 : count($result)';
            if ($hydrator['collection'] !== true) {
                $countString = 'null === $result ? 0 : 1';
            }

            $this->writeCacheFile(
                $cacheDir . DIRECTORY_SEPARATOR . $className . '.php',
                <<<PHP
<?php
namespace $namespace;

class $className extends \\$parentClassName
{
    public function hydrateAll(\$stmt, \$resultSetMapping, array \$hints = [])
    {
        if (\$logger = \$this->_em->getConfiguration()->getAttribute('OrmProfilingLogger')) {
            \$logger->startHydration('$hydratorName');
            \$result = parent::hydrateAll(\$stmt, \$resultSetMapping, \$hints);
            \$logger->stopHydration($countString, \$resultSetMapping->getAliasMap());
            return \$result;
        }
        return parent::hydrateAll(\$stmt, \$resultSetMapping, \$hints);
    }
}
PHP
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
