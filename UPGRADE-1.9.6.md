UPGRADE FROM 1.9.5 to 1.9.6
=======================

####EmailBundle
- The constructor of the `Oro\Bundle\EmailBundle\Manager\EmailNotificationManager` class was changed.
    Before: `__construct(EntityManager $entityManager, HtmlTagHelper $htmlTagHelper, Router $router, EmailCacheManager $emailCacheManager, ConfigManager $configManager)`.
    After: `__construct(EntityManager $entityManager, HtmlTagHelper $htmlTagHelper, Router $router, ConfigManager $configManager)`.

####DataGridBundle
- The constructor of the `Oro\Bundle\DataGridBundle\Extension\MassAction\DeleteMassActionHandler` class was changed.
    Before: `__construct(EntityManager $entityManager, TranslatorInterface $translator, SecurityFacade $securityFacade, MassDeleteLimiter $limiter, RequestStack $requestStack)`.
    After: `__construct(RegistryInterface $registry, TranslatorInterface $translator, SecurityFacade $securityFacade, MassDeleteLimiter $limiter, RequestStack $requestStack, OptionalListenerManager $listenerManager)`.
