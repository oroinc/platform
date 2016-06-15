UPGRADE FROM 1.8.3 to 1.8.4
=======================

####EmailBundle
- The constructor of the `Oro\Bundle\EmailBundle\Mailer\Processor` class was changed. Before: `__construct(DoctrineHelper $doctrineHelper, DirectMailer $mailer, EmailAddressHelper $emailAddressHelper, EmailEntityBuilder $emailEntityBuilder, EmailOwnerProvider $emailOwnerProvider, EmailActivityManager $emailActivityManager, ServiceLink $serviceLink, EventDispatcherInterface $eventDispatcher, Mcrypt $encryptor, EmailOriginHelper $emailOriginHelper)`. After: `__construct(DoctrineHelper $doctrineHelper, DirectMailer $mailer, EmailAddressHelper $emailAddressHelper, EmailEntityBuilder $emailEntityBuilder, EmailActivityManager $emailActivityManager, EventDispatcherInterface $eventDispatcher, Mcrypt $encryptor, EmailOriginHelper $emailOriginHelper)`.
- Additional you should use origin as second parameter for `Oro\Bundle\EmailBundle\Mailer\Processor::process` if you want use specific transport different from system.
