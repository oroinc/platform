UPGRADE FROM 1.8.3 to 1.8.4
=======================

####EmailBundle
- The constructor of the `Oro\Bundle\EmailBundle\Mailer\Processor` class was changed. Before: `__construct(DoctrineHelper $doctrineHelper, DirectMailer $mailer, EmailAddressHelper $emailAddressHelper, EmailEntityBuilder $emailEntityBuilder, EmailOwnerProvider $emailOwnerProvider, EmailActivityManager $emailActivityManager, ServiceLink $serviceLink, EventDispatcherInterface $eventDispatcher, Mcrypt $encryptor, EmailOriginHelper $emailOriginHelper)`. After: `__construct(DoctrineHelper $doctrineHelper, DirectMailer $mailer, EmailAddressHelper $emailAddressHelper, EmailEntityBuilder $emailEntityBuilder, EmailActivityManager $emailActivityManager, EventDispatcherInterface $eventDispatcher, Mcrypt $encryptor, EmailOriginHelper $emailOriginHelper)`.
- Additional you should use origin as second parameter for `Oro\Bundle\EmailBundle\Mailer\Processor::process` if you want use specific transport different from system.

####CalendarBundle
- Added method `formatCalendarDateRangeUser` of `src/Oro/src/Oro/Bundle/CalendarBundle/Twig/DateFormatUserExtension.php`. Method `calendar_date_range_user` get additional param 'user' and return sate range according to user organization localization settings.
 
####LocaleBundle:
- Added `oro_format_datetime_user` twig extension - allows get formatted date and calendar date range by user organization localization settings. Deprecated since 1.11. Will be removed after 1.13.
