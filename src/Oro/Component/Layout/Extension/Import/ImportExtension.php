<?php

namespace Oro\Component\Layout\Extension\Import;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\Extension\AbstractLayoutUpdateLoaderExtension;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\Extension\Theme\Model\DependencyInitializer;
use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\LayoutUpdateImportInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Oro\Component\Layout\Model\LayoutUpdateImport;

class ImportExtension extends AbstractLayoutUpdateLoaderExtension
{
    const IMPORT_FOLDER = 'imports';

    /** @var LayoutUpdateLoaderInterface */
    protected $loader;

    /** @var ThemeManager */
    protected $themeManager;

    /** @var DependencyInitializer */
    protected $dependencyInitializer;

    /**
     * @param array $resources
     * @param LayoutUpdateLoaderInterface $loader
     * @param DependencyInitializer $dependencyInitializer
     * @param PathProviderInterface $provider
     * @param ThemeManager $themeManager
     */
    public function __construct(
        array $resources,
        LayoutUpdateLoaderInterface $loader,
        DependencyInitializer $dependencyInitializer,
        PathProviderInterface $provider,
        ThemeManager $themeManager
    ) {
        parent::__construct($resources, $provider);

        $this->loader = $loader;
        $this->dependencyInitializer = $dependencyInitializer;
        $this->themeManager = $themeManager;

    }

    /**
     * {@inheritdoc}
     */
    protected function loadLayoutUpdate($file, ContextInterface $context)
    {
        $update = $this->loader->load($file);
        if ($update) {
            if ($update instanceof ImportsAwareLayoutUpdateInterface) {
                $this->loadImportUpdate($update, $context);
            }
        }
    }

    /**
     * @param LayoutUpdateInterface|ImportsAwareLayoutUpdateInterface $update
     * @param ContextInterface $context
     *
     * @return LayoutUpdateImportInterface
     */
    protected function loadImportUpdate(LayoutUpdateInterface $update, ContextInterface $context)
    {
        $this->dependencyInitializer->initialize($update);
        
        // load imports
        $imports = $update->getImports();
        if (!is_array($imports)) {
            throw new LogicException(
                sprintf('Imports statement should be an array, %s given', gettype($imports))
            );
        }

        foreach ($imports as $importData) {
            $import = $this->createImport($importData);
            $files = $this->getImportResources($context->get(static::THEME_KEY), $import->getId());
            foreach ($files as $file) {
                /** @var LayoutUpdateInterface|LayoutUpdateImportInterface $importUpdate */
                $importUpdate = $this->loader->load($file);
                $importUpdate->setImport($import);
                $importUpdate->setParentUpdate($update);

                $this->updates[$this->getElement($importUpdate)][] = $importUpdate;

                if ($importUpdate instanceof ImportsAwareLayoutUpdateInterface) {
                    $this->loadImportUpdate($importUpdate, $context);
                }
            }
        }
    }

    /**
     * @param string|array $importProperties
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
     * @param string $themeName
     * @param string $importId
     *
     * @return array
     */
    protected function getImportResources($themeName, $importId)
    {
        $theme = $this->themeManager->getTheme($themeName);

        $importPath = implode(
            PathProviderInterface::DELIMITER,
            [$theme->getName(), static::IMPORT_FOLDER, $importId]
        );

        $files = $this->findApplicableResources([$importPath]);
        if ($theme->getParentTheme()) {
            $files = array_merge($this->getImportResources($theme->getParentTheme(), $importId), $files);
        }

        return $files;
    }
}
