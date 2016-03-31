UPGRADE FROM 1.9 to 1.9.1
=======================

####DashboardBundle:
- Class `Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateTimeRangeConverter` was renamed to `Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter`. Service was not renamed.
- Added new class `Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateTimeRangeConverter`.

####PlatformBundle
- Before this changes configs from `app.yml` were loading by newest - first. Now they are loading like newest - last. Method `prepend()` in `Oro\Bundle\PlatformBundle\DependencyInjection\OroPlatformExtension` now will load resources from bundle in recursive way `$resources = array_reverse($configLoader->load());`
