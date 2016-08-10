<?php

namespace Oro\Component\Layout\Extension\Import;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\Extension\AbstractLayoutUpdateLoaderExtension;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\Extension\Theme\ThemeExtension;
use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\LayoutUpdateImportInterface;
use Oro\Component\Layout\Loader\LayoutUpdateLoaderInterface;
use Oro\Component\Layout\Model\LayoutUpdateImport;

class ImportExtension extends AbstractLayoutUpdateLoaderExtension
{
    const IMPORT_FOLDER = 'imports';

    /** @var array */
    protected $resources;

    /** @var LayoutUpdateLoaderInterface */
    protected $loader;

    /** @var  ThemeManager */
    protected $themeManager;

    /**
     * @param array $resources
     * @param LayoutUpdateLoaderInterface $loader
     * @param ThemeManager $themeManager
     */
    public function __construct(
        array $resources,
        LayoutUpdateLoaderInterface $loader,
        ThemeManager $themeManager
    ) {
        $this->resources = $resources;
        $this->loader = $loader;
        $this->themeManager = $themeManager;
    }

    /**
     * @param $file
     * @param ContextInterface $context
     *
     * @return array
     */
    protected function loadImports($file, ContextInterface $context)
    {
        $update = $this->loader->load($file);
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
                $files = $this->getImportResources($context->get(ThemeExtension::THEME_KEY), $import->getId());
                foreach ($files as $file) {
                    $importUpdate = $this->loadImports($file, $context);
                    if ($importUpdate instanceof LayoutUpdateImportInterface) {
                        $importUpdate->setImport($import);
                        $importUpdate->setParentUpdate($update);
                    }
                }
            }
        }

        return $update;
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
            [
                $theme->getName(),
                static::IMPORT_FOLDER,
                $importId,
            ]
        );

        $files = $this->findApplicableResources([$importPath]);
        if ($theme->getParentTheme()) {
            $files = array_merge(
                $this->getImportResources($theme->getParentTheme(), $importId),
                $files
            );
        }

        return $files;
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
}
