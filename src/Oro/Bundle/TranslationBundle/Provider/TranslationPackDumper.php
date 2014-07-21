<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\Extractor\ExtractorInterface;

use Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TranslationPackDumper implements LoggerAwareInterface
{
    /** @var TranslationWriter */
    protected $writer;

    /** @var ExtractorInterface */
    protected $extractor;

    /** @var TranslationLoader */
    protected $loader;

    /** @var Filesystem */
    protected $filesystem;

    /** @var LoggerInterface */
    protected $logger;

    /** @var BundleInterface[] */
    protected $bundles;

    /** @var MessageCatalogue[] Translations loaded from yaml files, existing */
    protected $loadedTranslations = [];

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
        $this->logger     = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
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

            $this->logger->log(LogLevel::INFO, sprintf('Writing files for <info>%s</info>', $bundle->getName()));

            $messageCatalogue = $this->getMergedTranslations(
                $locale,
                $bundle->getPath() . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR
            );

            $isEmptyCatalogue = $this->validateAndFilter($messageCatalogue);
            if (!$isEmptyCatalogue) {
                $translationsDir = $langPackDir . DIRECTORY_SEPARATOR .
                    $bundle->getName() . DIRECTORY_SEPARATOR . 'translations';
                $this->filesystem->mkdir($translationsDir);

                $this->writer->writeTranslations(
                    $messageCatalogue,
                    $outputFormat,
                    ['path' => $translationsDir]
                );
            } else {
                $this->logger->log(LogLevel::INFO, sprintf('    - no files generated for <info>%s</info>', $bundle->getName()));
            }
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
            $this->loadedTranslations[] = $currentCatalogue;
        }

        $operation = new MergeOperation($currentCatalogue, $extractedCatalogue);

        return $operation->getResult();
    }

    /**
     * @param MessageCatalogue $messageCatalogue
     *
     * @return bool
     */
    protected function validateAndFilter(MessageCatalogue $messageCatalogue)
    {
        $allMessages   = $messageCatalogue->all();
        $notTranslated = [];
        $isEmpty       = true;

        foreach ($allMessages as $domain => $messages) {
            foreach ($messages as $key => $value) {
                // key is something like %segment.name%, so called parameter
                if (preg_match('#^%[^%\s]*%$#', $key)) {
                    $messages[$key] = false;
                    $notTranslated[$domain][] = $key;
                    continue;
                }

                $isTranslationExist = $this->checkTranslationExists($key, $domain);
                $isDottedKey        = preg_match('#^[^\s\.]\.[^\s\.].?#', $key);
                $isKeyValueEqual    = $key == $value;

                // untranslated string, and translation doesn't exist in any other catalogue
                // remove it and warn
                if ($isDottedKey && $isKeyValueEqual && !$isTranslationExist) {
                    $messages[$key] = false;
                    $notTranslated[$domain][] = $key;
                    continue;
                }

                // untranslated dotted string, but translation exists in some other catalogue
                if ($isDottedKey && $isKeyValueEqual && $isTranslationExist) {
                    $messages[$key] = false;
                    $notTranslated[$domain][] = $key;
                    continue;
                }

                // dotted key that finish with dot, e.g. segment.type. meaning that suffix can be added dynamically
                if ($isKeyValueEqual && $isDottedKey && '.' == $key[strlen($key)-1]) {
                    $messages[$key] = false;
                    $notTranslated[$domain][] = $key;
                    continue;
                }
            }

            $cleanMessages = array_filter($messages);
            $messageCatalogue->replace($cleanMessages, $domain);
            $isEmpty = $isEmpty && empty($cleanMessages);
        }

        foreach ($notTranslated as $domain => $messages) {
            $this->logger->error(sprintf('  Wrong translation strings in %s, skipped', $domain));
            foreach ($messages as $message) {
                $this->logger->info('   - ' . $message);
            }
        }

        return $isEmpty;
    }

    /**
     * Check if key exists in loaded catalogue and it's not equal to value, e.g. have translation
     *
     * @param string $key
     * @param string $domain
     *
     * @return bool
     */
    protected function checkTranslationExists($key, $domain)
    {
        foreach ($this->loadedTranslations as $catalogue) {
            if ($key != $catalogue->get($key, $domain)) {
                return true;
            }
        }

        return false;
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
