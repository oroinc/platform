UPGRADE FROM 2.3 to 2.4
=======================

**IMPORTANT**
-------------

Some inline underscore templates from next bundles, were moved to separate .html file for each template:
 - DataGridBundle
 - FilterBundle
 - UIBundle

DashboardBundle
--------
- Class `Oro\Bundle\DashboardBundle\Helper\DateHelper`
    - In method `addDatePartsSelect` removed the last one argument `$useCurrentTimeZone`
    - In method `getEnforcedTimezoneFunction` removed the last one argument `$useCurrentTimeZone`

ImportExportBundle
--------
- Functionality for showing import/export buttons for multiple entities were added:
    - Class `Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration` was added for storing import/export parameters
    - Interface `Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface` was added for specifying import/export parameters
    - Interface `Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface` was added with responsibility to provide import/export configuration
    - Class `Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationRegistry` was added to store and provide all configurations provided by configuration providers
    - Interface `Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationRegistryInterface` was added with responsibility to provide import/export configurations
    - Class `Oro\Bundle\ImportExportBundle\DependencyInjection\Compiler\ImportExportConfigurationRegistryCompilerPass` was added to fill the registry with configuration providers
    - View `buttons_from_configuration.html.twig` was added for showing import/export buttons for multiple entities on a page
    - Class `Oro\Bundle\ImportExportBundle\Twig\GetImportExportConfigurationExtension` was added to provide access to configurations from view
- Class `Oro\Bundle\ImportExportBundle\Tests\Functional\AbstractImportExportTest` was added as a base class for creating functional tests for import/export features

UIBundle
--------
- `'oroui/js/tools'` JS-module does not contain utils methods from `Caplin.utils` any more. Require `'chaplin'` directly to get access to them.

SyncBundle
----------
- Class `Oro\Bundle\SyncBundle\Content\DoctrineTagGenerator`
    - removed property `generatedTags`
    - removed method `getCacheIdentifier`
    
DataGridBundle
--------------
- Class `Oro\Bundle\DataGridBundle\Extension\Sorter\PreciseOrderByExtension` was renamed to `Oro\Bundle\DataGridBundle\Extension\Sorter\HintExtension`.
 Hint name and priority now passed as 2nd and 3rd constructor arguments
- `HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS` was enabled by default for all data grids. To enable order by nulls behavior same to MySQL for PostgreSQL 
 next hint should be added to data grid config
```yaml
datagrids:
    grid-name:
       ...
       source:
           ...
           hints:
               - { name: HINT_DISABLE_ORDER_BY_MODIFICATION_NULLS, value: false }
```
