UPGRADE FROM 1.9 to 1.9.1
=======================

####DashboardBundle:
- Class `Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateTimeRangeConverter` was renamed to `Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateRangeConverter`. Service was not renamed.
- Added new class `Oro\Bundle\DashboardBundle\Provider\Converters\FilterDateTimeRangeConverter`.

####PlatformBundle
- Method `prepend()` in `Oro\Bundle\PlatformBundle\DependencyInjection\OroPlatformExtension` was changed. Now the configuration from `Resources\config\oro\app.yml` is loaded in reverce order. It means that bundles that are loaded later can override configuration of bundles loaded before.
