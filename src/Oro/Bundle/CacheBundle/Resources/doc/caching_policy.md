Caching policy
==============

Table of Contents
-----------------
 - [Memory based cache](#memory-based-cache)
 - [Persistent/shared cache](#persistent/shared-cache)
 - [Cache chaining](#cache-chaining)
 - [Default cache implementation](#default-cache-implementation)


Memory based cache
==================

One of the most important thing when dealing with caches is proper cache invalidation.
When using memory based cache we need to make sure that we do not keep old values in memory. Consider this example:

```php
<?php

class LocalizationManager
{
    /** @var \Doctrine\Common\Cache\ArrayCache */
    private $cacheProvider;
    
    public function getLocalization($id)
    {
        $localization = $this->cacheProvider->fetch($id);
        
        // ... all other operations, fetch from DB if cache is empty
        // ... save in cache data from DB
        
        return $localization;
    }

}
```

Since `$cacheProvider` in our example is an implementation of memory `ArrayCache`, we will keep the data there until the process ends. With HTTP request this would work perfectly well, but when our `LocalizationManager` would be used in some long-running cli 
processes, we have to manually clear memory cache after every change with Localizations. 
Missing cache clearing for any of these cases leads to the outdated data in `LocalizationManager`.

Persistent/shared cache
=======================

Let's have a look at our example once again. Since `LocalizationManager` is used in the CLI and we do not have the shared 
memory, we would not be able to invalidate the cache between different processes. We probably would go for some
more persistent (shared) way of caching, for example, `FilesystemCache`. Now, we are able to share cache between
processes, but this approach causes performance degradation. In general, a memory cache is much faster than a persistent one.


Cache chaining
==============

Solution to the issue mentioned above is to keep a healthy balance between the fast and shared cache. It is implemented in the [MemoryCacheChain](../../Provider/MemoryCacheChain.php) class.

This class checks whether a request comes from the CLI. If not, the memory `ArrayCache` is added to the top
of the cache providers which are being used for caching. With these priorities set, all HTTP requests gain performance when dealing with caches in memory and the CLI processes have no issues with the outdated data as they use the persistent cache.


Default cache implementation
============================

As you may read in [Readme](../../readme.md#abstract-cache-services) there are three abstract services you can use as a
parent for your cache services. The default implementation for services based on
`oro.file_cache.abstract` and `oro.cache.abstract` is following:

 - for CLI requests: `MemoryCacheChain` with only `Oro\Bundle\CacheBundle\Provider\FilesystemCache` as a cache provider
 - for other requests: `MemoryCacheChain` with `ArrayCache` on the top of `FilesystemCache`

For services based on `oro.cache.abstract.without_memory_cache` the `MemoryCacheChain` is not used.
