UPGRADE FROM 2.2.2 to 2.2.3
===========================

Table of Contents
-----------------

- [ReportBundle](#reportbundle)

ReportBundle
------------

- Class Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationProvider was modified to use doctrine cache instead of caching DatagridConfiguration value in property $configuration.
To set values for $prefixCacheKey and $reportCacheManager in ReportDatagridConfigurationProvider, following methods were added:
     - public method `setPrefixCacheKey($prefixCacheKey)`
     - public method `setReportCacheManager(Cache $reportCacheManager)`

     They will be removed in version 2.3 and $prefixCacheKey and $reportCacheManager will be initialized in constructor

     Before
     ```PHP
        class ReportDatagridConfigurationProvider
        {
            /**
             * @var DatagridConfiguration
             */
            protected $configuration;

            public function getConfiguration($gridName)
            {
                if ($this->configuration === null) {
                    ...
                    $this->configuration = $this->builder->getConfiguration();
                }

                return $this->configuration;
            }
        }
     ```

     After
     ```PHP
        class ReportDatagridConfigurationProvider
        {
            /**
             * Doctrine\Common\Cache\Cache
             */
            protected $reportCacheManager;

            public function getConfiguration($gridName)
            {
                $cacheKey = $this->getCacheKey($gridName);

                if ($this->reportCacheManager->contains($cacheKey)) {
                    $config = $this->reportCacheManager->fetch($cacheKey);
                    $config = unserialize($config);
                } else {
                    $config = $this->prepareConfiguration($gridName);
                    $this->reportCacheManager->save($cacheKey, serialize($config));
                }

                return $config;
            }
        }
     ```

- Class Oro\Bundle\ReportBundle\EventListener\ReportCacheCleanerListener was added. It cleans cache of report grid on postUpdate event of Report entity.
