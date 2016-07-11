<?php

namespace Oro\Component\Layout\Extension\Theme;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\Extension\AbstractExtension;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\LayoutUpdateImportInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Oro\Component\Layout\Loader\Generator\ElementDependentLayoutUpdateInterface;
use Oro\Component\Layout\Model\LayoutUpdateImport;

class ThemeExtension extends AbstractExtension
{
    const THEME_KEY = 'theme';
    const IMPORT_FOLDER = 'imports';

    /** @var array */
    protected $resources;

    /** @var LayoutUpdateLoaderInterface */
    protected $loader;

    /** @var DependencyInitializer */
    protected $dependencyInitializer;

    /** @var PathProviderInterface */
    protected $pathProvider;

    /** @var  array */
    protected $updates;

    /**
     * @param array $resources
     * @param LayoutUpdateLoaderInterface $loader
     * @param DependencyInitializer $dependencyInitializer
     * @param PathProviderInterface $provider
     */
    public function __construct(
        array $resources,
        LayoutUpdateLoaderInterface $loader,
        DependencyInitializer $dependencyInitializer,
        PathProviderInterface $provider
    ) {
        $this->resources = $resources;
        $this->loader = $loader;
        $this->dependencyInitializer = $dependencyInitializer;
        $this->pathProvider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdates(ContextInterface $context)
    {
        $this->updates = [];
        if ($context->getOr(static::THEME_KEY)) {
            $paths = $this->getPaths($context);
            $files = $this->findApplicableResources($paths);
            foreach ($files as $file) {
                $this->loadLayoutUpdate($file, $context);
            }
        }

        return $this->updates;
    }

    /**
     * @param                    $file
     * @param ContextInterface $context
     *
     * @return array
     */
    protected function loadLayoutUpdate($file, ContextInterface $context)
    {
        $update = $this->loader->load($file);
        if ($update) {
            $el = $update instanceof ElementDependentLayoutUpdateInterface
                ? $update->getElement()
                : 'root';
            $this->updates[$el][] = $update;

            $this->dependencyInitializer->initialize($update);

            if ($update instanceof ImportsAwareLayoutUpdateInterface) {
                // load imports
                $imports = $update->getImports();
                if (!is_array($imports)) {
                    throw new LogicException(
                        sprintf('Imports statement should be an array, %s given', gettype($imports))
                    );
                }
                foreach ($imports as $importData) {
                    $import = $this->createImport($importData);
                    $importPaths = $this->getImportPaths($context, $import->getId());
                    $files = $this->findApplicableResources($importPaths);
                    foreach ($files as $file) {
                        $importUpdate = $this->loadLayoutUpdate($file, $context);
                        if ($importUpdate instanceof LayoutUpdateImportInterface) {
                            $importUpdate->setImport($import);
                        }
                    }
                }
            }
        }

        return $update;
    }

    /**
     * Return paths that comes from provider and returns array of resource files
     *
     * @param ContextInterface $context
     *
     * @return array
     */
    protected function getPaths(ContextInterface $context)
    {
        if ($this->pathProvider instanceof ContextAwareInterface) {
            $this->pathProvider->setContext($context);
        }

        return $this->pathProvider->getPaths([]);
    }

    /**
     * @param ContextInterface $context
     * @param string $importId
     *
     * @return string
     */
    protected function getImportPaths(ContextInterface $context, $importId)
    {
        return [
            implode(
                PathProviderInterface::DELIMITER,
                [
                    $context->get(static::THEME_KEY),
                    static::IMPORT_FOLDER,
                    $importId,
                ]
            )
        ];
    }

    /**
     * @param $importProperties
     *
     * @return LayoutUpdateImport
     */
    protected function createImport($importProperties)
    {
        if (!is_array($importProperties)) {
            $importProperties = [ImportsAwareLayoutUpdateInterface::ID_KEY => $importProperties];
        }

        return LayoutUpdateImport::createFromArray($importProperties);
    }

    /**
     * Filters resources by paths
     *
     * @param array $paths
     *
     * @return array
     */
    protected function findApplicableResources(array $paths)
    {
        $result = [];
        foreach ($paths as $path) {
            $pathArray = explode(PathProviderInterface::DELIMITER, $path);

            $value = $this->resources;
            for ($i = 0, $length = count($pathArray); $i < $length; ++$i) {
                $value = $this->readValue($value, $pathArray[$i]);

                if (null === $value) {
                    break;
                }
            }

            if ($value && is_array($value)) {
                $result = array_merge($result, array_filter($value, 'is_string'));
            }
        }

        return $result;
    }

    /**
     * @param array $array
     * @param string $property
     *
     * @return mixed
     */
    protected function readValue(&$array, $property)
    {
        if (is_array($array) && isset($array[$property])) {
            return $array[$property];
        }

        return null;
    }
}
