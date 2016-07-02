UPGRADE FROM 1.9.1 to 1.9.2
=======================

####SearchBundle
- `Oro\Bundle\SearchBundle\DependencyInjection\OroSearchExtension::mergeConfig` deprecated since 1.9.2 Will be removed after 1.11.

####CalendarBundle
- Added method `formatCalendarDateRangeOrganization` of `src/Oro/src/Oro/Bundle/CalendarBundle/Twig/DateFormatOrganizationExtension.php`. Method `calendar_date_range_organization` get additional param 'organization' and return sate range according to organization localization settings.
 
####LocaleBundle:
- Added `oro_format_datetime_user` twig extension - allows get formatted date and calendar date range by user organization localization settings. Deprecated since 1.11. Will be removed after 1.13.
