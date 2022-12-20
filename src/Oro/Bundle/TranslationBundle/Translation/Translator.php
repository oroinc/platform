<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Oro\Bundle\CacheBundle\Provider\MemoryCache;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * Decorates Symfony translator by extending it, adds loading of dynamic resources for translations and ability to
 * select different translation strategies.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Translator extends BaseTranslator
{
    public const DEFAULT_LOCALE = 'en';

    private MessageFormatter $messageFormatter;
    private TranslationStrategyProvider $strategyProvider;
    private MemoryCache $resourceCache;
    private LoggerInterface $logger;
    private MessageCatalogueSanitizer $catalogueSanitizer;
    private TranslationMessageSanitizationErrorCollection $sanitizationErrorCollection;
    private ?DynamicTranslationProviderInterface $dynamicTranslationProvider = null;
    private array $originalOptions;
    private array $resourceFiles;
    private array $cacheVary;
    private array $loadedFallbackLocales = [];
    private ?string $appliedStrategyName = null;
    private ?string $appliedLocale = null;
    private bool $enableDumpCatalogue = false;
    private bool $disableResetCatalogues = false;

    public function __construct(
        ContainerInterface $container,
        MessageFormatter $formatter,
        string $defaultLocale = null,
        array $loaderIds = [],
        array $options = []
    ) {
        parent::__construct($container, $formatter, $defaultLocale, $loaderIds, $options);

        $this->messageFormatter = $formatter;
        $this->originalOptions = $this->options;
        $this->resourceFiles = $this->options['resource_files'];
        $this->cacheVary = $this->options['cache_vary'] ?? [];
        $this->logger = new NullLogger();
    }

    public function setStrategyProvider(TranslationStrategyProvider $strategyProvider): void
    {
        $this->strategyProvider = $strategyProvider;
    }

    public function setResourceCache(MemoryCache $cache): void
    {
        $this->resourceCache = $cache;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setMessageCatalogueSanitizer(MessageCatalogueSanitizer $catalogueSanitizer): void
    {
        $this->catalogueSanitizer = $catalogueSanitizer;
    }

    public function setSanitizationErrorCollection(TranslationMessageSanitizationErrorCollection $collection): void
    {
        $this->sanitizationErrorCollection = $collection;
    }

    public function setDynamicTranslationProvider(DynamicTranslationProviderInterface $provider): void
    {
        $this->dynamicTranslationProvider = $provider;
        $this->dynamicTranslationProvider->setFallbackLocales($this->getFallbackLocales());
    }

    /**
     * Collects all translations for the given domains and locale,
     * takes in account fallback of locales.
     * Method is used for exposing of collected translations.
     *
     * @param string[]    $domains The list of domains; the empty list means all domains
     * @param string|null $locale  The locale or null to use the default
     *
     * @return array [domain => [message id => message, ...], ...]
     */
    public function getTranslations(array $domains = [], ?string $locale = null): array
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        }

        /** @var MessageCatalogueInterface[] $fallbackCatalogues */
        $fallbackCatalogues = [];
        $catalogue = $this->getCatalogue($locale);
        while (null !== $catalogue) {
            $fallbackCatalogues[] = $catalogue;
            $catalogue = $catalogue->getFallbackCatalogue();
        }
        $fallbackCatalogues = array_reverse($fallbackCatalogues);

        $translations = [];
        foreach ($fallbackCatalogues as $fallbackCatalogue) {
            $localeTranslations = $fallbackCatalogue->all();
            foreach ($domains as $domain) {
                $domainTranslations = $localeTranslations[$domain] ?? [];
                $translations[$domain] = empty($translations[$domain])
                    ? $domainTranslations
                    : array_merge($translations[$domain], $domainTranslations);
                $dynamicTranslations = $this->dynamicTranslationProvider->getTranslations(
                    $domain,
                    $fallbackCatalogue->getLocale()
                );
                if ($dynamicTranslations) {
                    $translations[$domain] = array_replace($translations[$domain], $dynamicTranslations);
                }
            }
        }

        return $translations;
    }

    /**
     * {@inheritDoc}
     */
    public function trans(?string $id, array $parameters = [], string $domain = null, string $locale = null): string
    {
        if (!$id) {
            return '';
        }

        if (null === $locale) {
            $locale = $this->getLocale();
        }
        if (null === $domain) {
            $domain = 'messages';
        }

        $catalogue = $this->getCatalogue($locale);
        while (null !== $catalogue) {
            $catalogueLocale = $catalogue->getLocale();
            if ($this->dynamicTranslationProvider->hasTranslation($id, $domain, $catalogueLocale)) {
                return $this->messageFormatter->format(
                    $this->dynamicTranslationProvider->getTranslation($id, $domain, $catalogueLocale),
                    $catalogueLocale,
                    $parameters
                );
            }
            if ($catalogue->defines($id, $domain)) {
                break;
            }
            $catalogue = $catalogue->getFallbackCatalogue();
        }

        try {
            return parent::trans($id, $parameters, $domain, $locale);
        } catch (InvalidArgumentException $e) {
            $this->logger->warning($e->getMessage(), ['exception' => $e]);

            $count = '';
            if (isset($parameters['%count%'])) {
                $count = ' ' . $parameters['%count%'];
                unset($parameters['%count%']);
            }

            return $this->trans($id, $parameters, $domain, $locale) . $count;
        }
    }

    /**
     * Checks if the given message has a translation.
     */
    public function hasTrans(string $id, ?string $domain = null, ?string $locale = null): bool
    {
        if (!$id) {
            return false;
        }

        if (null === $locale) {
            $locale = $this->getLocale();
        }
        if (null === $domain) {
            $domain = 'messages';
        }

        $result = false;
        $catalogue = $this->getCatalogue($locale);
        while (null !== $catalogue) {
            if ($this->dynamicTranslationProvider->hasTranslation($id, $domain, $catalogue->getLocale())
                || $catalogue->defines($id, $domain)
            ) {
                $result = true;
                break;
            }
            $catalogue = $catalogue->getFallbackCatalogue();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function addLoader(string $format, LoaderInterface $loader): void
    {
        // wrap a resource loader by a caching loader to prevent loading of the same resource several times
        // it strongly decreases a translation catalogue loading time
        // for example a time of translation cache warming up is decreased in about 4 times
        parent::addLoader($format, new CachingTranslationLoader($loader, $this->resourceCache));
    }

    /**
     * {@inheritDoc}
     */
    public function setFallbackLocales(array $locales): void
    {
        $loadedCatalogues = $this->disableResetCatalogues ? $this->catalogues : [];

        parent::setFallbackLocales($locales);
        $this->dynamicTranslationProvider?->setFallbackLocales($locales);
        $this->cacheVary['fallback_locales'] = $locales;

        foreach ($loadedCatalogues as $locale => $catalogue) {
            if (!isset($this->catalogues[$locale])) {
                $this->catalogues[$locale] = $catalogue;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getCatalogue(string $locale = null): MessageCatalogueInterface
    {
        $this->applyFallbackLocales($locale);

        return parent::getCatalogue($locale);
    }

    /**
     * {@inheritDoc}
     */
    public function addResource(string $format, $resource, string $locale, string $domain = null): void
    {
        if (\is_string($resource)) {
            $this->resourceFiles[$locale][] = $resource;
        }

        parent::addResource($format, $resource, $locale, $domain);
    }

    /**
     * {@inheritDoc}
     */
    public function warmUp(string $cacheDir): array
    {
        // skip warmUp when translator doesn't use cache
        if (null !== $this->options['cache_dir']) {
            $this->rebuildCache();
        }

        return [];
    }

    /**
     * Rebuilds all cached message catalogues.
     */
    public function rebuildCache(): void
    {
        $cacheDir = $this->originalOptions['cache_dir'];
        $options = array_merge($this->originalOptions, [
            'cache_dir'      => $cacheDir . uniqid('', true),
            'resource_files' => array_map(static function (array $localeResources) {
                return array_unique($localeResources);
            }, $this->resourceFiles)
        ]);

        $currentStrategy = $this->strategyProvider->getStrategy();
        try {
            $strategies = $this->strategyProvider->getStrategies();
            foreach ($strategies as $strategy) {
                $this->buildCatalogueFiles($strategy, $cacheDir, $options);
            }
        } finally {
            $this->strategyProvider->setStrategy($currentStrategy);
            $this->applyFallbackLocales();
        }
    }

    private function buildCatalogueFiles(
        TranslationStrategyInterface $strategy,
        string $cacheDir,
        array $options
    ): void {
        $this->strategyProvider->setStrategy($strategy);

        $locales = $this->strategyProvider->getAllFallbackLocales($strategy);
        foreach ($locales as $locale) {
            $this->newTranslator($locale, $options)->loadCatalogues();
        }
        $this->dynamicTranslationProvider->warmUp($locales);
        $this->moveCatalogueFiles($options['cache_dir'], $cacheDir);
    }


    private function newTranslator(string $locale, array $options): static
    {
        $translator = new static($this->container, $this->messageFormatter, $locale, $this->loaderIds, $options);
        $translator->setStrategyProvider($this->strategyProvider);
        $translator->setResourceCache($this->resourceCache);
        $translator->setLogger($this->logger);
        $translator->setMessageCatalogueSanitizer($this->catalogueSanitizer);
        $translator->setSanitizationErrorCollection($this->sanitizationErrorCollection);
        $translator->setDynamicTranslationProvider($this->dynamicTranslationProvider);

        return $translator;
    }

    private function loadCatalogues(): void
    {
        $locale = $this->getLocale();
        $this->applyFallbackLocales($locale);

        $this->enableDumpCatalogue = true;
        try {
            $this->loadCatalogue($locale);
        } finally {
            $this->enableDumpCatalogue = false;
        }
        $sanitizationErrors = $this->sanitizationErrorCollection->all();
        foreach ($sanitizationErrors as $sanitizationError) {
            $this->logger->warning('Unsafe translation message found', ['error' => $sanitizationError]);
        }
    }

    private function moveCatalogueFiles(string $fromDir, string $toDir): void
    {
        $filesystem = new Filesystem();
        $iterator = new \IteratorIterator(new \DirectoryIterator($fromDir));
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filePath = $file->getPathname();
                $filesystem->copy($filePath, $toDir . DIRECTORY_SEPARATOR . $file->getFilename(), true);
                $filesystem->remove($filePath);
            }
        }
        $filesystem->remove($fromDir);
    }

    private function applyFallbackLocales(?string $locale = null): void
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        }
        $strategyName = $this->strategyProvider->getStrategy()->getName();
        if ($this->appliedLocale !== $locale || $this->appliedStrategyName !== $strategyName) {
            $this->appliedLocale = $locale;
            $this->appliedStrategyName = $strategyName;
            $this->setFallbackLocales($this->computeFallbackLocales($locale));
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function computeFallbackLocales($locale): array
    {
        $strategy = $this->strategyProvider->getStrategy();
        $strategyName = $strategy->getName();
        if (!isset($this->loadedFallbackLocales[$strategyName][$locale])) {
            $this->loadedFallbackLocales[$strategyName][$locale] = $this->strategyProvider->getFallbackLocales(
                $strategy,
                $locale
            );
        }

        return $this->loadedFallbackLocales[$strategyName][$locale];
    }

    /**
     * {@inheritDoc}
     */
    protected function loadCatalogue($locale): void
    {
        if ($this->enableDumpCatalogue || $this->isCatalogueCacheFileExits($this->getCatalogueCachePath($locale))) {
            parent::loadCatalogue($locale);
        } else {
            // make sure that all fallback catalogues are loaded
            // to avoid re-initialization of already dumped catalogues
            $fallbackLocales = $this->computeFallbackLocales($locale);
            if ($fallbackLocales) {
                $currentLocale = $this->getLocale();
                $this->disableResetCatalogues = true;
                try {
                    foreach ($fallbackLocales as $fallbackLocale) {
                        if ($fallbackLocale !== $locale) {
                            $this->getCatalogue($fallbackLocale);
                        }
                    }
                    $this->applyFallbackLocales($currentLocale);
                } finally {
                    $this->disableResetCatalogues = false;
                }
            }
            // initialize empty catalogue
            $this->initializeCatalogue($locale);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(): void
    {
        // save already loaded catalogues which are used as fallbacks to prevent their reloading second time
        // it change Symfony`s translator behavior and allows us to apply only already dumped catalogues in fallbacks
        $loadedCatalogues = array_intersect_key($this->catalogues, array_flip($this->getFallbackLocales()));

        parent::initialize();

        // restore already loaded catalogues
        $this->catalogues = array_merge($this->catalogues, array_diff_key($loadedCatalogues, $this->catalogues));
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeCatalogue($locale): void
    {
        parent::initializeCatalogue($locale);

        if (isset($this->catalogues[$locale])) {
            $this->catalogueSanitizer->sanitizeCatalogue($this->catalogues[$locale]);
        } else {
            $this->catalogues[$locale] = new MessageCatalogue($locale);
        }
    }

    private function getCatalogueCachePath(string $locale): string
    {
        return $this->options['cache_dir']
            . '/catalogue.'
            . $locale
            . '.'
            . strtr(
                substr(base64_encode(hash('sha256', serialize($this->cacheVary), true)), 0, 7),
                '/',
                '_'
            )
            . '.php';
    }

    private function isCatalogueCacheFileExits(string $path): bool
    {
        clearstatcache(true, $path);

        return is_file($path);
    }
}
