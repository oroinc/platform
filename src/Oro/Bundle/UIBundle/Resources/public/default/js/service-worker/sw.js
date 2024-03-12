/**
 * If there is a Response in the cache, the Request will be fulfilled using the cached response and the network will not be used at all.
 * If there isn't a cached response, the Request will be fulfilled by a network request and the response will be cached
 * so that the next request is served directly from the cache.
 * @param {string} cacheVersion
 * @param {Object} event
 */
const cacheFirst = (cacheVersion, event) => {
    event.respondWith(
        caches.match(event.request).then(cacheResponse => {
            return cacheResponse || fetch(event.request).then(networkResponse => {
                return caches.open(cacheVersion).then(cache => {
                    cache.put(event.request, networkResponse.clone());
                    return networkResponse;
                });
            });
        })
    );
};

const params = new URLSearchParams(location.search.substring(1));
const assetVersion = params.get('v');
const themeName = params.get('theme');

if (!themeName) {
    throw new Error('"themeName" is required');
}

if (!assetVersion) {
    throw new Error('"assetVersion" is required');
}

const cacheVersion = themeName + assetVersion;
const routes = {
    find: url => routes.urls.find(route => url.match(route.url)),
    urls: [
        {url: `build\/${themeName}\/svg-icons\/theme-icons\\.svg`, handle: cacheFirst.bind(cacheFirst, cacheVersion)}
    ]
};

addEventListener('fetch', event => {
    const found = routes.find(event.request.url);

    if (found) {
        found.handle(event);
    }
});

addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => Promise.all(
            cacheNames
                .filter(cacheName => cacheName !== cacheVersion)
                .map(cacheName => caches.delete(cacheName))
        ))
    );
});


