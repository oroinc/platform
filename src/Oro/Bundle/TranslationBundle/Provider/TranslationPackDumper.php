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
        $this->preloadExistingTranslations($locale);
        foreach ($this->bundles as $bundle) {
            // skip bundles from other projects
            if ($projectNamespace != $this->getBundlePrefix($bundle->getNamespace())) {
                continue;
            }

            $this->logger->log(LogLevel::INFO,  '');
            $this->logger->log(LogLevel::INFO, sprintf('Writing files for <info>%s</info>', $bundle->getName()));

            /** @var MessageCatalogue $currentCatalogue */
            $currentCatalogue   = $this->getCurrentCatalog($locale, $bundle->getName());
            $extractedCatalogue = $this->extractViewTranslationKeys($locale, $bundle->getPath());

            $operation = new MergeOperation($currentCatalogue, $extractedCatalogue);
            $messageCatalogue = $operation->getResult();

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
                $this->logger->log(LogLevel::INFO, '    - no files generated');
            }
        }
    }

    /**
     * @param string $locale
     * @param string $bundlePath
     *
     * @return MessageCatalogue
     */
    protected function extractViewTranslationKeys($locale, $bundlePath)
    {
        $extractedCatalogue = new MessageCatalogue($locale);
        $bundleViewsPath    = $bundlePath . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'views';

        if ($this->filesystem->exists($bundleViewsPath)) {
            $this->extractor->extract($bundleViewsPath, $extractedCatalogue);
        }

        return $extractedCatalogue;
    }

    /**
     * @param string $locale
     * @param string $bundleName
     *
     * @return bool|MessageCatalogue
     */
    protected function getCurrentCatalog($locale, $bundleName)
    {
        return empty($this->loadedTranslations[$bundleName]) ?
            new MessageCatalogue($locale) :
            $this->loadedTranslations[$bundleName];
    }


    /**
     * Preload existring translations to check against duplicates
     *
     * @param string $locale
     */
    protected function preloadExistingTranslations($locale)
    {
        foreach ($this->bundles as $bundle) {
            $translationsPath = $bundle->getPath() . DIRECTORY_SEPARATOR . 'Resources' .
                DIRECTORY_SEPARATOR .  'translations';

            $currentCatalogue   = new MessageCatalogue($locale);
            if ($this->filesystem->exists($translationsPath)) {
                $this->loader->loadMessages($translationsPath, $currentCatalogue);
                $this->loadedTranslations[$bundle->getName()] = $currentCatalogue;
            }
        }
    }

    /**
     * @param MessageCatalogue $messageCatalogue
     *
     * @return bool
     */
    protected function validateAndFilter(MessageCatalogue $messageCatalogue)
    {
        $allMessages       = $messageCatalogue->all();
        $notTranslatedKeys = [];
        $isEmpty           = true;

        foreach ($allMessages as $domain => $messages) {
            foreach ($messages as $key => $value) {
                // key is something like %segment.name%, so called parameter
                if (preg_match('#^%[^%\s]+%$#', $key)) {
                    $messages[$key] = false;
                    continue;
                }

                $isTranslationExist = $this->checkTranslationExists($key);
                $isDottedKey        = (bool) preg_match('#^[^\s\.]+\.(?:[^\s\.]+\.?)+$#', $key);
                $isKeyValueEqual    = $key == $value;

                // untranslated dotted string, but translation exists in some other catalogue
                if ($isDottedKey && $isKeyValueEqual && $isTranslationExist) {
                    $messages[$key] = false;
                    continue;
                }

                // dotted key that finish with dot, e.g. segment.type. meaning that suffix can be added dynamically
                $isDotAtTheEnd = $isKeyValueEqual && $isDottedKey && '.' == $key[strlen($key) - 1];
                $isPlaceholder = $isKeyValueEqual && $isDottedKey && '%s' == substr($key, -2);
                if ($isDotAtTheEnd || $isPlaceholder) {
                    $messages[$key] = false;
                    continue;
                }

                // untranslated string, and translation doesn't exist in any other catalogue
                // remove it and warn
                if ($isDottedKey && $isKeyValueEqual && !$isTranslationExist) {
                    $messages[$key] = false;
                    $notTranslatedKeys[$domain][] = $key;
                    continue;
                }
            }

            $cleanMessages = array_filter($messages);
            $messageCatalogue->replace($cleanMessages, $domain);
            $isEmpty = $isEmpty && empty($cleanMessages);
        }

        foreach ($notTranslatedKeys as $domain => $messages) {
            $this->logger->error(sprintf('  skipped not translated keys in "%s" domain', $domain));
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
     *
     * @return bool
     */
    protected function checkTranslationExists($key)
    {
        foreach ($this->loadedTranslations as $catalogue) {
            foreach ($catalogue->getDomains() as $domain) {
                // key not equal to value only if translation exists
                if ($key != $catalogue->get($key, $domain)) {
                    return true;
                }
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
