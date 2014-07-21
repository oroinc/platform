<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\Extractor\ExtractorInterface;

use Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader;

class TranslationPackDumper
{
    /** @var TranslationWriter */
    protected $writer;

    /** @var ExtractorInterface */
    protected $extractor;

    /** @var TranslationLoader */
    protected $loader;

    /** @var Filesystem */
    protected $filesystem;

    /** @var BundleInterface[] */
    protected $bundles;

    public function __construct(
        TranslationWriter $writer,
        ExtractorInterface $extractor,
        TranslationLoader $loader,
        Filesystem $filesystem,
        array $bundles
    ) {
        $this->writer     = $writer;
        $this->extractor  = $extractor;
        $this->loader     = $loader;
        $this->filesystem = $filesystem;
        $this->bundles    = $bundles;
    }

    /**
     * @param string $langPackDir       language pack dir in temp folder
     * @param string $projectNamespace  e.g. Oro, OroCRM, etc
     * @param string $outputFormat      xml, yml, etc
     * @param string $locale            en, en_US, fr, etc
     */
    public function dump($langPackDir, $projectNamespace, $outputFormat, $locale)
    {
        foreach ($this->bundles as $bundle) {
            // skip bundles from other projects
            if ($projectNamespace != $this->getBundlePrefix($bundle->getNamespace())) {
                continue;
            }

            $translationsDir = $langPackDir . DIRECTORY_SEPARATOR .
                $bundle->getName() . DIRECTORY_SEPARATOR . 'translations';
            $this->filesystem->mkdir($translationsDir);

            $messageCatalogue = $this->getMergedTranslations(
                $locale,
                $bundle->getPath() . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR
            );

            $this->writer->writeTranslations(
                $messageCatalogue,
                $outputFormat,
                ['path' => $translationsDir]
            );
        }
    }

    /**
     * Merge current and extracted translations
     *
     * @param string $defaultLocale
     * @param string $bundleResourcesPath   path to bundle resources folder
     *
     * @return MessageCatalogue
     */
    protected function getMergedTranslations($defaultLocale, $bundleResourcesPath)
    {
        $bundleTransPath = $bundleResourcesPath . 'translations' . DIRECTORY_SEPARATOR;
        $bundleViewsPath = $bundleResourcesPath . 'views' . DIRECTORY_SEPARATOR;

        $currentCatalogue   = new MessageCatalogue($defaultLocale);
        $extractedCatalogue = new MessageCatalogue($defaultLocale);

        if ($this->filesystem->exists($bundleViewsPath)) {
            $this->extractor->extract($bundleViewsPath, $extractedCatalogue);
        }

        if ($this->filesystem->exists($bundleTransPath)) {
            $this->loader->loadMessages($bundleTransPath, $currentCatalogue);
        }

        $operation = new MergeOperation($currentCatalogue, $extractedCatalogue);

        return $operation->getResult();
    }

    /**
     * Get project name from bundle namespace
     * e.g. Oro\TestBundle -> Oro
     *
     * @param string $bundleNamespace
     *
     * @return string
     */
    protected function getBundlePrefix($bundleNamespace)
    {
        return substr($bundleNamespace, 0, strpos($bundleNamespace, '\\'));
    }
}
