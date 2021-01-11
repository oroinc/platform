<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractEntityGeneratorExtension;
use Oro\Component\PhpUtils\ClassGenerator;
use Symfony\Component\Yaml\Yaml;

/**
 * Builds proxy classes and ORM mapping for extended entities.
 */
class EntityGenerator
{
    private string $cacheDir;

    private string $entityCacheDir;

    /** @var iterable|AbstractEntityGeneratorExtension[] */
    private $extensions;

    /**
     * @param string $cacheDir
     * @param iterable|AbstractEntityGeneratorExtension[] $extensions
     */
    public function __construct(string $cacheDir, iterable $extensions)
    {
        $this->setCacheDir($cacheDir);
        $this->extensions = $extensions;
    }

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    public function setCacheDir(string $cacheDir): void
    {
        $this->cacheDir = $cacheDir;
        $this->entityCacheDir = ExtendClassLoadingUtils::getEntityCacheDir($cacheDir);
    }

    /**
     * Generates extended entities
     */
    public function generate(array $schemas): void
    {
        ExtendClassLoadingUtils::ensureDirExists($this->entityCacheDir);

        $aliases = [];
        foreach ($schemas as $schema) {
            $this->generateSchemaFiles($schema);
            if ('Extend' === $schema['type']) {
                $aliases[$schema['entity']] = $schema['parent'];
            }
        }

        // write PHP class aliases to the file
        \file_put_contents(
            ExtendClassLoadingUtils::getAliasesPath($this->cacheDir),
            \serialize($aliases)
        );
    }

    /**
     * Generates php and yml files for schema
     */
    public function generateSchemaFiles(array $schema): void
    {
        $class = new ClassGenerator($schema['entity']);
        if ('mappedSuperclass' === $schema['doctrine'][$schema['entity']]['type']) {
            $class->setAbstract(true);
        }

        foreach ($this->extensions as $extension) {
            if ($extension->supports($schema)) {
                $extension->generate($schema, $class);
            }
        }

        $className = ExtendHelper::getShortClassName($schema['entity']);

        // write PHP class to the file
        $fileName = $this->entityCacheDir . DIRECTORY_SEPARATOR . $className . '.php';
        \file_put_contents($fileName, "<?php\n\n" . $class->print());
        \clearstatcache(true, $fileName);
        // write doctrine metadata in separate yaml file
        \file_put_contents(
            $this->entityCacheDir . DIRECTORY_SEPARATOR . $className . '.orm.yml',
            Yaml::dump($schema['doctrine'], 5)
        );
    }
}
