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
    private string $entityClassesPath;
    /** @var iterable|AbstractEntityGeneratorExtension[] */
    private $extensions;

    /**
     * @param string                                      $cacheDir
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
        $this->entityClassesPath = ExtendClassLoadingUtils::getEntityClassesPath($cacheDir);
    }

    /**
     * Generates extended entities
     */
    public function generate(array $schemas): void
    {
        $this->ensureEntityCacheDirExistsAndEmpty();

        $aliases = [];
        $classes = '';
        foreach ($schemas as $schema) {
            if ('Extend' === $schema['type']) {
                $aliases[$schema['entity']] = $schema['parent'];
            }
            $classes .= $this->buildPhpClass($schema);
            $this->writeOrmMapping($schema);
        }
        // writes PHP classes for extended entity proxy to PHP file contains all such classes
        $this->writePhpFile($this->entityClassesPath, $this->buildPhpFileHeader() . $classes);

        // write PHP class aliases to the file
        $this->writePhpFile(
            ExtendClassLoadingUtils::getAliasesPath($this->cacheDir),
            sprintf('<?php return %s;', var_export($aliases, true))
        );
    }

    /**
     * Generates PHP class for extended entity proxy and YAML file with ORM mapping.
     */
    public function generateSchemaFiles(array $schema): void
    {
        $class = $this->buildPhpClass($schema);
        $classes = '';
        if (file_exists($this->entityClassesPath)) {
            $classes = file_get_contents($this->entityClassesPath);
        }
        if ($classes) {
            $classes = $this->replacePhpClass($classes, $class, ExtendHelper::getShortClassName($schema['entity']));
        } else {
            $classes = $this->buildPhpFileHeader() . $class;
        }
        $this->writePhpFile($this->entityClassesPath, $classes);
        $this->writeOrmMapping($schema);
    }

    private function ensureEntityCacheDirExistsAndEmpty(): void
    {
        ExtendClassLoadingUtils::ensureDirExists($this->entityCacheDir);
        $iterator = new \DirectoryIterator($this->entityCacheDir);
        /** @var \DirectoryIterator $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                @unlink($file->getPathname());
            }
        }
    }

    /**
     * Builds a header for a file contains all PHP classes for all extended entity proxies.
     */
    private function buildPhpFileHeader(): string
    {
        return sprintf("<?php\n\nnamespace %s;\n", ExtendClassLoadingUtils::getEntityNamespace());
    }

    /**
     * Builds a header for extended entity proxy PHP class.
     */
    private function buildPhpClassHeader(string $shortClassName): string
    {
        return "\n" . sprintf('/** Start: %s */', $shortClassName) . "\n";
    }

    /**
     * Builds a footer for extended entity proxy PHP class.
     */
    private function buildPhpClassFooter(string $shortClassName): string
    {
        return sprintf('/** End: %s */', $shortClassName) . "\n";
    }

    /**
     * Builds PHP class for extended entity proxy.
     */
    private function buildPhpClass(array $schema): string
    {
        $class = new ClassGenerator($schema['entity']);
        if ('mappedSuperclass' === $schema['doctrine'][$schema['entity']]['type']) {
            $class->setAbstract();
        }

        foreach ($this->extensions as $extension) {
            if ($extension->supports($schema)) {
                $extension->generate($schema, $class);
            }
        }

        $shortClassName = ExtendHelper::getShortClassName($schema['entity']);

        return
            $this->buildPhpClassHeader($shortClassName)
            . $class->printSkipNamespace()
            . $this->buildPhpClassFooter($shortClassName);
    }

    /**
     * Replaces an old definition of the PHP class with the new definition.
     */
    private function replacePhpClass(string $classes, string $class, string $shortClassName): string
    {
        // remove old definition of the class
        $classHeader = $this->buildPhpClassHeader($shortClassName);
        $startPos = strpos($classes, $classHeader);
        if (false !== $startPos) {
            $classFooter = $this->buildPhpClassFooter($shortClassName);
            $endPos = strpos($classes, $classFooter, $startPos);
            if (false !== $endPos) {
                $classes = substr($classes, 0, $startPos) . substr($classes, $endPos + \strlen($classFooter));
            }
        }

        return $classes . $class;
    }

    /**
     * Writes the given content into the given PHP file.
     */
    private function writePhpFile(string $path, string $content): void
    {
        file_put_contents($path, $content);
        clearstatcache(true, $path);
    }

    /**
     * Writes ORM mapping in separate YAML file.
     */
    private function writeOrmMapping(array $schema): void
    {
        $shortClassName = ExtendHelper::getShortClassName($schema['entity']);
        file_put_contents(
            $this->entityCacheDir . DIRECTORY_SEPARATOR . $shortClassName . '.orm.yml',
            Yaml::dump($schema['doctrine'], 5)
        );
    }
}
