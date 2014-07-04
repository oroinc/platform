<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Symfony\Component\Yaml\Yaml;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;

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
        $aliases = [];
        foreach ($schemas as $schema) {
            // generate PHP code
            $class = PhpClass::create($schema['entity']);
            foreach ($this->getExtensions() as $extension) {
                if ($extension->supports($schema)) {
                    $extension->generate($schema, $class);
                }
            }

            $className = ExtendHelper::getShortClassName($schema['entity']);
            if ($schema['type'] == 'Extend') {
                $aliases[$schema['entity']] = $schema['parent'];
            }

            // write PHP class to the file
            $strategy = new DefaultGeneratorStrategy();
            file_put_contents(
                $this->entityCacheDir . DIRECTORY_SEPARATOR . $className . '.php',
                "<?php\n\n" . $strategy->generate($class)
            );
            // write doctrine metadata in separate yaml file
            file_put_contents(
                $this->entityCacheDir . DIRECTORY_SEPARATOR . $className . '.orm.yml',
                Yaml::dump($schema['doctrine'], 5)
            );
        }

        // write PHP class aliases to the file
        file_put_contents(
            ExtendClassLoadingUtils::getAliasesPath($this->cacheDir),
            Yaml::dump($aliases)
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
            $this->sortedExtensions = call_user_func_array('array_merge', $this->extensions);
        }

        return $this->sortedExtensions;
    }
}
