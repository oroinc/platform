<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;
use Oro\Bundle\TranslationBundle\Event\AfterCatalogueDump;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\Formatter\MessageFormatter;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Decorates Symfony translator by extending it, adds loading of dynamic resources for translations and ability to
 * select different translation strategies.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Translator extends BaseTranslator
{
    const DEFAULT_LOCALE = 'en';

    /** @var DynamicTranslationMetadataCache|null */
    protected $databaseTranslationMetadataCache;

    /** @var Cache|null */
    protected $resourceCache;

    /**
     * @var array
     *  [
     *      locale => [
     *          [
     *              'resource' => DynamicResourceInterface,
     *              'format'   => string,
     *              'code'     => string,
     *              'domain'   => string
     *          ],
     *          ...
     *      ],
     *      ...
     *  ]
     */
    protected $dynamicResources = [];

    /** @var array */
    protected $registeredResources = [];

    /** @var bool */
    protected $installed;

    /** @var string|null */
    protected $strategyName;

    /** @var MessageFormatter */
    protected $messageFormatter;

    /** @var array */
    protected $originalOptions;

    /** @var array */
    protected $resourceFiles = [];

    /** @var TranslationStrategyProvider */
    private $strategyProvider;

    /** @var TranslationDomainProvider */
    private $translationDomainProvider;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $cacheVary;

    /**
     * @param ContainerInterface $container
     * @param MessageFormatter $formatter
     * @param null $defaultLocale
     * @param array $loaderIds
     * @param array $options
     */
    public function __construct(
        ContainerInterface $container,
        MessageFormatter $formatter,
        $defaultLocale = null,
        $loaderIds = [],
        array $options = []
    ) {
        parent::__construct($container, $formatter, $defaultLocale, $loaderIds, $options);

        $this->messageFormatter = $formatter;
        $this->originalOptions = $this->options;
        $this->resourceFiles = $this->options['resource_files'];
        $this->cacheVary = $this->options['cache_vary'] ?? [];
        $this->logger = new NullLogger();
    }

    public function setStrategyProvider(TranslationStrategyProvider $strategyProvider)
    {
        $this->strategyProvider = $strategyProvider;
    }

    public function setTranslationDomainProvider(TranslationDomainProvider $translationDomainProvider)
    {
        $this->translationDomainProvider = $translationDomainProvider;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setInstalled($installed)
    {
        $this->installed = $installed;
    }

    /**
     * Collector of translations
     *
     * Collects all translations for corresponded domains and locale,
     * takes in account fallback of locales.
     * Method is used for exposing of collected translations.
     *
     * @param array       $domains list of required domains, by default empty, means all domains
     * @param string|null $locale  locale of translations, by default is current locale
     *
     * @return array
     */
    public function getTranslations(array $domains = array(), $locale = null)
    {
        // if new strategy was selected
        if ($this->strategyProvider->getStrategy()->getName() !== $this->strategyName) {
            $this->applyCurrentStrategy();
        }

        if (null === $locale) {
            $locale = $this->getLocale();
        }

        if (!isset($this->catalogues[$locale])) {
            $this->loadCatalogue($locale);
        }

        $fallbackCatalogues   = array();
        $fallbackCatalogues[] = $catalogue = $this->catalogues[$locale];
        while ($catalogue = $catalogue->getFallbackCatalogue()) {
            $fallbackCatalogues[] = $catalogue;
        }

        $domains      = array_flip($domains);
        $translations = array();
        for ($i = count($fallbackCatalogues) - 1; $i >= 0; $i--) {
            $localeTranslations = $fallbackCatalogues[$i]->all();
            // if there are domains -> filter only their translations
            if ($domains) {
                $localeTranslations = array_intersect_key($localeTranslations, $domains);
            }
            foreach ($localeTranslations as $domain => $domainTranslations) {
                if (!empty($translations[$domain])) {
                    $translations[$domain] = array_merge($translations[$domain], $domainTranslations);
                } else {
                    $translations[$domain] = $domainTranslations;
                }
            }
        }

        return $translations;
    }

    /**
     * {@inheritdoc}
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
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
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        try {
            return parent::transChoice($id, $number, $parameters, $domain, $locale);
        } catch (InvalidArgumentException $e) {
            $this->logger->warning($e->getMessage(), ['exception' => $e]);

            return $this->trans($id, $parameters, $domain, $locale) . ' ' . $number;
        }
    }

    /**
     * Checks if the given message has a translation.
     *
     * @param string $id     The message id (may also be an object that can be cast to string)
     * @param string $domain The domain for the message
     * @param string $locale The locale
     *
     * @return bool Whether string have translation
     */
    public function hasTrans($id, $domain = null, $locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        }

        if (null === $domain) {
            $domain = 'messages';
        }

        if (!isset($this->catalogues[$locale])) {
            $this->loadCatalogue($locale);
        }

        $id = (string)$id;

        $catalogue = $this->catalogues[$locale];
        $result    = $catalogue->defines($id, $domain);
        while (!$result && $catalogue = $catalogue->getFallbackCatalogue()) {
            $result = $catalogue->defines($id, $domain);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function addLoader($format, LoaderInterface $loader)
    {
        if (null !== $this->resourceCache) {
            // wrap a resource loader by a caching loader to prevent loading of the same resource several times
            // it strongly decreases a translation catalogue loading time
            // for example a time of translation cache warming up is decreased in about 4 times
            $loader = new CachingTranslationLoader($loader, $this->resourceCache);
        }
        parent::addLoader($format, $loader);
    }

    /**
     * {@inheritdoc}
     */
    public function getCatalogue($locale = null)
    {
        // if new strategy was selected
        if ($this->strategyProvider->getStrategy()->getName() !== $this->strategyName) {
            $this->applyCurrentStrategy();
        }

        return parent::getCatalogue($locale);
    }

    /**
     * {@inheritdoc}
     */
    public function addResource($format, $resource, $locale, $domain = null)
    {
        if (is_string($resource)) {
            $this->resourceFiles[$locale][] = $resource;
        }

        parent::addResource($format, $resource, $locale, $domain);
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        // skip warmUp when translator doesn't use cache
        if (null === $this->options['cache_dir']) {
            return;
        }

        $this->applyCurrentStrategy();

        // load catalogues only for needed locales. We cannot call parent::warmUp() because it would load catalogues
        // for all resources found in filesystem
        $locales = array_unique($this->getFallbackLocales());
        foreach ($locales as $locale) {
            // no need to reset catalogue like it is done in parent::warmUp() because all catalogues are already cleared
            // in applyCurrentStrategy(), so we are just loading new catalogue
            $this->loadCatalogue($locale);
        }
    }

    /**
     * Removes all cached message catalogs.
     */
    public function clearCache()
    {
        $this->applyCurrentStrategy();
        $locales = array_unique($this->getFallbackLocales());
        foreach ($locales as $locale) {
            $catalogueFile = $this->getCatalogueCachePath($locale);
            if ($this->isCatalogueCacheFileExits($catalogueFile)) {
                unlink($catalogueFile);
            }
        }
    }

    /**
     * Rebuilds all cached message catalogs, w/o any delay at clients side
     */
    public function rebuildCache()
    {
        $cacheDir = $this->originalOptions['cache_dir'];

        $tmpDir = $cacheDir . uniqid('', true);

        $options = array_merge(
            $this->originalOptions,
            [
                'cache_dir' => $tmpDir,
                'resource_files' => array_map(
                    function (array $localeResources) {
                        return array_unique($localeResources);
                    },
                    $this->resourceFiles
                )
            ]
        );

        // save current translation strategy
        $currentStrategy = $this->strategyProvider->getStrategy();

        // build translation cache for each translation strategy in tmp cache directory
        foreach ($this->strategyProvider->getStrategies() as $strategy) {
            $this->strategyProvider->setStrategy($strategy);

            /* @var $translator Translator */
            $translator = new static(
                $this->container,
                $this->messageFormatter,
                $this->getLocale(),
                $this->loaderIds,
                $options
            );

            $translator->setStrategyProvider($this->strategyProvider);
            $translator->setTranslationDomainProvider($this->translationDomainProvider);
            $translator->setEventDispatcher($this->eventDispatcher);
            $translator->setInstalled($this->installed);
            $translator->setDatabaseMetadataCache($this->databaseTranslationMetadataCache);

            $translator->warmUp($tmpDir);
        }

        $filesystem = new Filesystem();

        // replace current cache with new cache
        $iterator = new \IteratorIterator(new \DirectoryIterator($tmpDir));
        foreach ($iterator as $path) {
            if (!$path->isFile()) {
                continue;
            }
            $filesystem->copy($path->getPathName(), $cacheDir . DIRECTORY_SEPARATOR . $path->getFileName(), true);
            $filesystem->remove($path->getPathName());
        }

        $filesystem->remove($tmpDir);

        // restore translation strategy and apply it to make use of new cache
        $this->strategyProvider->setStrategy($currentStrategy);
        $this->applyCurrentStrategy();
    }

    /**
     * Sets a cache of dynamic translation metadata
     */
    public function setDatabaseMetadataCache(DynamicTranslationMetadataCache $cache)
    {
        $this->databaseTranslationMetadataCache = $cache;
    }

    /**
     * Sets a cache of loaded translation resources
     */
    public function setResourceCache(Cache $cache)
    {
        $this->resourceCache = $cache;
    }

    protected function applyCurrentStrategy()
    {
        $strategy = $this->strategyProvider->getStrategy();

        // store current strategy name to skip all following requests to it
        $this->strategyName = $strategy->getName();

        // use current set of fallback locales to build translation cache
        $fallbackLocales = $this->strategyProvider->getAllFallbackLocales($strategy);

        // set new fallback locales and clear catalogues to generate new ones for new strategy
        $this->setFallbackLocales($fallbackLocales);
        $this->cacheVary['fallback_locales'] = $fallbackLocales;
    }

    /**
     * {@inheritdoc}
     */
    protected function computeFallbackLocales($locale)
    {
        return $this->strategyProvider->getFallbackLocales($this->strategyProvider->getStrategy(), $locale);
    }

    /**
     * {@inheritdoc}
     */
    protected function loadCatalogue($locale)
    {
        $this->initializeDynamicResources($locale);
        $isCacheReady = $this->isCatalogueCacheFileExits($this->getCatalogueCachePath($locale));
        parent::loadCatalogue($locale);

        if (!$isCacheReady && $this->isApplicationInstalled()) {
            $this->eventDispatcher->dispatch(
                new AfterCatalogueDump($this->catalogues[$locale]),
                AfterCatalogueDump::NAME
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        // save already loaded catalogues which are used as fallbacks to prevent their reloading second time
        // it change Symfony`s translator behavior and allows us to apply only already dumped catalogues in fallbacks
        $loadedCatalogues = array_intersect_key($this->catalogues, array_flip($this->getFallbackLocales()));

        // add dynamic resources just before the initialization
        // to be sure that they overrides static translations
        $this->registerDynamicResources();

        parent::initialize();

        // restore already loaded catalogues
        $this->catalogues = array_merge($this->catalogues, array_diff_key($loadedCatalogues, $this->catalogues));
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeCatalogue($locale)
    {
        parent::initializeCatalogue($locale);
        if (!isset($this->catalogues[$locale])) {
            $this->catalogues[$locale] = new MessageCatalogue($locale);
        }
    }

    /**
     * Initializes dynamic translation resources
     *
     * @param string $locale
     */
    protected function initializeDynamicResources($locale)
    {
        $this->ensureDynamicResourcesLoaded($locale);

        // check if any dynamic resource is changed and update translation catalogue if needed
        if (!empty($this->dynamicResources[$locale])) {
            $catalogueFile = $this->getCatalogueCachePath($locale);
            if ($this->isCatalogueCacheFileExits($catalogueFile)) {
                $time = filemtime($catalogueFile);
                foreach ($this->dynamicResources[$locale] as $item) {
                    /** @var DynamicResourceInterface $dynamicResource */
                    $dynamicResource = $item['resource'];
                    if (!$dynamicResource->isFresh($time)) {
                        // remove translation catalogue to allow parent class to rebuild it
                        unlink($catalogueFile);
                        // make sure that translations will be loaded from source resources
                        if ($this->resourceCache instanceof ClearableCache) {
                            $this->resourceCache->deleteAll();
                        }
                        clearstatcache(true, $catalogueFile);

                        break;
                    }
                }
            }
        }
    }

    private function getCatalogueCachePath(string $locale): string
    {
        return $this->options['cache_dir'] .'/catalogue.' .$locale .'.' .strtr(
            substr(base64_encode(hash('sha256', serialize($this->cacheVary), true)), 0, 7),
            '/',
            '_'
        ).'.php';
    }

    private function isCatalogueCacheFileExits(string $path): bool
    {
        clearstatcache(true, $path);

        return is_file($path);
    }

    /**
     * Adds dynamic translation resources to the translator
     */
    protected function registerDynamicResources()
    {
        foreach ($this->dynamicResources as $items) {
            foreach ($items as $item) {
                if (in_array($item, $this->registeredResources, true)) {
                    continue;
                }
                $this->registeredResources[] = $item;
                $this->addResource($item['format'], $item['resource'], $item['code'], $item['domain']);
            }
        }
    }

    /**
     * Makes sure that dynamic translation resources are added to $this->dynamicResources
     *
     * @param string $locale
     */
    protected function ensureDynamicResourcesLoaded($locale)
    {
        if (null !== $this->databaseTranslationMetadataCache && $this->isApplicationInstalled()) {
            $hasDatabaseResources = false;
            if (!empty($this->dynamicResources[$locale])) {
                foreach ($this->dynamicResources[$locale] as $item) {
                    if ($item['format'] === 'oro_database_translation') {
                        $hasDatabaseResources = true;
                        break;
                    }
                }
            }
            if (!$hasDatabaseResources) {
                $locales = $this->getFallbackLocales();
                array_unshift($locales, $locale);
                $locales = array_unique($locales);

                $availableDomainsData = $this->translationDomainProvider
                    ->getAvailableDomainsForLocales($locales);
                foreach ($availableDomainsData as $item) {
                    $item['resource'] = new OrmTranslationResource(
                        $item['code'],
                        $this->databaseTranslationMetadataCache
                    );
                    $item['format'] = 'oro_database_translation';

                    $this->dynamicResources[$item['code']][] = $item;
                }
            }
        }
    }

    /**
     * @return bool
     */
    private function isApplicationInstalled()
    {
        return !empty($this->installed);
    }
}
