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
