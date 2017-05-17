UPGRADE FROM 2.2 to 2.3
=======================

MigrationBundle
---------------
- Added event `oro_migration.data_fixtures.pre_load` that is raised before data fixtures are loaded
- Added event `oro_migration.data_fixtures.post_load` that is raised after data fixtures are loaded

SearchBundle
------------
- Class `Oro\Bundle\SearchBundle\EventListener\ReindexDemoDataListener` was replaced with `Oro\Bundle\SearchBundle\EventListener\ReindexDemoDataFixturesListener`
- Service `oro_search.event_listener.reindex_demo_data` was replaced with `oro_search.migration.demo_data_fixtures_listener.reindex`

IntegrationBundle
-----------------

- Interface `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface` was changed:
  - Methods `getRawHeaders`, `xml`, `getRedirectCount`, `getEffectiveUrl` were completely removed
  - Methods `getContentEncoding`, `getContentLanguage`, `getContentLength`, `getContentLocation`, `getContentDisposition`, `getContentMd5`, `getContentRange`, `getContentType`, `isContentType` were superseded by `getHeader` method 
- Interface `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface` was changed:
  - Method `getXML` was completely removed

- Class `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestClient` method `getXML` was removed please use simple `get` method instead and conver it's result to XML
- Class `Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestResponse`:
  - Methods `getRawHeaders`, `xml`, `getRedirectCount`, `getEffectiveUrl` were removed in case you need them just use construction like `$response->getSourceResponse()->xml()`
  - Methods `getContentEncoding`, `getContentLanguage`, `getContentLength`, `getContentLocation`, `getContentDisposition`, `getContentMd5`, `getContentRange`, `getContentType`, `isContentType` were removed but you can get same values if you use `$response->getHeader('Content-Type')` or `$response->getHeader('Content-MD5')` for example. 
