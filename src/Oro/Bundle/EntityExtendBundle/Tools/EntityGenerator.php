<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;
use Symfony\Component\Yaml\Yaml;

/**
 * Builds proxy classes and ORM mapping for extended entities.
 */
class EntityGenerator
{
    /** @var string */
    protected $cacheDir;

    /** @var string */
    protected $entityCacheDir;

    /** @var array */
    protected $extensions = [];

    /** @var AbstractEntityGeneratorExtension[]|null */
    protected $sortedExtensions;

    /**
     * @param string $cacheDir
     */
    public function __construct($cacheDir)
    {
        $this->setCacheDir($cacheDir);
    }

    /**
     * Gets the cache directory
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * Sets the cache directory
     *
     * @param string $cacheDir
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir       = $cacheDir;
        $this->entityCacheDir = ExtendClassLoadingUtils::getEntityCacheDir($cacheDir);
    }

    /**
     * @param AbstractEntityGeneratorExtension $extension
     * @param int                              $priority
     */
    public function addExtension(AbstractEntityGeneratorExtension $extension, $priority = 0)
    {
        if (!isset($this->extensions[$priority])) {
            $this->extensions[$priority] = [];
        }
        $this->extensions[$priority][] = $extension;
    }

    /**
     * Generates extended entities
     *
     * @param array $schemas
     */
    public function generate(array $schemas)
    {
        ExtendClassLoadingUtils::ensureDirExists($this->entityCacheDir);

        $aliases = [];
        foreach ($schemas as $schema) {
            $this->generateSchemaFiles($schema);
            if ($schema['type'] === 'Extend') {
                $aliases[$schema['entity']] = $schema['parent'];
            }
        }

        // write PHP class aliases to the file
        file_put_contents(
            ExtendClassLoadingUtils::getAliasesPath($this->cacheDir),
            serialize($aliases)
        );
    }

    /**
     * Generate php and yml files for schema
     *
     * @param array $schema
     */
    public function generateSchemaFiles(array $schema)
    {
        // generate PHP code
        $class = PhpClass::create($schema['entity']);
        if ($schema['doctrine'][$schema['entity']]['type'] === 'mappedSuperclass') {
            $class->setAbstract(true);
        }

        $extensions = $this->getExtensions();
        foreach ($extensions as $extension) {
            if ($extension->supports($schema)) {
                $extension->generate($schema, $class);
            }
        }

        $className = ExtendHelper::getShortClassName($schema['entity']);

        // write PHP class to the file
        $strategy = new DefaultGeneratorStrategy();
        $fileName = $this->entityCacheDir . DIRECTORY_SEPARATOR . $className . '.php';
        file_put_contents($fileName, "<?php\n\n" . $strategy->generate($class));
        clearstatcache(true, $fileName);
        // write doctrine metadata in separate yaml file
        file_put_contents(
            $this->entityCacheDir . DIRECTORY_SEPARATOR . $className . '.orm.yml',
            Yaml::dump($schema['doctrine'], 5)
        );
    }

    /**
     * Return sorted extensions
     *
     * @return AbstractEntityGeneratorExtension[]
     */
    protected function getExtensions()
    {
        if (null === $this->sortedExtensions) {
            krsort($this->extensions);
            $this->sortedExtensions = array_merge(...$this->extensions);
        }

        return $this->sortedExtensions;
    }
}
