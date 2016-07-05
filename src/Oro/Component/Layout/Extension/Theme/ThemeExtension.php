<?php

namespace Oro\Component\Layout\Extension\Theme;

use Doctrine\Common\Collections\Collection;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\Extension\AbstractExtension;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\LayoutUpdateImportInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
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
        $updates = [];
        if ($context->getOr(static::THEME_KEY)) {
            $files = $this->findApplicableResources($this->getProviderPaths($context));
            $this->loadLayoutUpdate($files, $context, $updates);
        }
        return $updates;
    }

    /**
     * @param array              $files
     * @param ContextInterface   $context
     * @param array              $updates
     * @param LayoutUpdateImport $import
     */
    protected function loadLayoutUpdatesWithImports(array $files, ContextInterface $context, array &$updates, LayoutUpdateImport $import = null)
    {
        // todo where is resource iterator

        foreach ($files as $file) {
            $update = $this->loader->load($file);
            if ($update) {
                $this->dependencyInitializer->initialize($update);

                if($update instanceof LayoutUpdateImportInterface) {
                    $update->setImport($import);
                }

                $el = $update instanceof ElementDependentLayoutUpdateInterface
                    ? $update->getElement()
                    : 'root';
                $updates[$el][] = $update;

                $this->loadImports($update, $context, $updates);
            }
        }
    }

    /**
     * @param LayoutUpdateInterface $update
     * @param ContextInterface $context
     * @param array $updates
     */
    protected function loadImports(LayoutUpdateInterface $update, ContextInterface $context, array &$updates)
    {
        if ($update instanceof ImportsAwareLayoutUpdateInterface) {
            $imports = $update->getImports();
            if (!is_array($imports)) {
                throw new LogicException(sprintf('Imports statement should be an array, %s given', gettype($imports)));
            }
            foreach ($imports as $importProperties) {
                $import = $this->createImport($importProperties);
                $importPaths = $this->getPathsForImport($context, $import->getId());
                $importFiles = $this->findApplicableResources($importPaths);
                $this->loadLayoutUpdate($importFiles, $context, $updates, $import);
            }
        }
    }

    /**
     * @param ContextInterface $context
     * @param string $importId
     * @return string
     */
    protected function getPathsForImport(ContextInterface $context, $importId)
    {
        return [
            implode(PathProviderInterface::DELIMITER, [
                $context->get(static::THEME_KEY),
                static::IMPORT_FOLDER,
                $importId,
            ])
        ];
    }

    /**
     * @param $importProperties
     * @return LayoutUpdateImport
     */
    protected function createImport($importProperties)
    {
        if (!is_array($importProperties)) {
            $importProperties = [ImportsAwareLayoutUpdateInterface::ID_KEY => $importProperties];
        }
        if (!array_key_exists(ImportsAwareLayoutUpdateInterface::ID_KEY, $importProperties)) {
            throw new LogicException(sprintf(
                'Import id should be provided, array with "%s" keys given',
                implode(', ', array_keys($importProperties))
            ));
        }
        $importProperties = array_merge([
            ImportsAwareLayoutUpdateInterface::ROOT_KEY => null,
            ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY => null,
        ], $importProperties);
        return new LayoutUpdateImport(
            $importProperties[ImportsAwareLayoutUpdateInterface::ID_KEY],
            $importProperties[ImportsAwareLayoutUpdateInterface::ROOT_KEY],
            $importProperties[ImportsAwareLayoutUpdateInterface::NAMESPACE_KEY]
        );
    }

    /**
     * @param string $file
     * @return null|LayoutUpdateInterface
     */
    protected function loadLayoutUpdateOld($file)
    {
        $update = $this->loader->load($file);
        if ($update) {
            $this->dependencyInitializer->initialize($update);
        }
        return $update;
    }

    /**
     * @param LayoutUpdateInterface $update
     * @param array $updates
     */
    protected function collectLayoutUpdates($update, array &$updates)
    {
        $el = $update instanceof ElementDependentLayoutUpdateInterface
            ? $update->getElement()
            : 'root';
        $updates[$el][] = $update;
    }

    /**
     * Return paths that comes from provider and returns array of resource files
     * @param ContextInterface $context
     * @return array
     */
    protected function getProviderPaths(ContextInterface $context)
    {
        if ($this->pathProvider instanceof ContextAwareInterface) {
            $this->pathProvider->setContext($context);
        }
        return $this->pathProvider->getPaths([]);
    }

    /**
     * Filters resources by paths
     * @param array $paths
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
