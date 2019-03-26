- [ActivityListBundle](#activitylistbundle)
- [ApiBundle](#apibundle)

ActivityListBundle
------------------
* The `ActivityListManager::getRelatedActivityEntities(ActivityList $entity, $entityProvider)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.2/src/Oro/Bundle/ActivityListBundle/Entity/Manager/ActivityListManager.php#L408 "Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager")</sup> method was changed to `ActivityListManager::getRelatedActivityEntities(ActivityList $entity, ActivityListProviderInterface $entityProvider, $activity)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/ActivityListBundle/Entity/Manager/ActivityListManager.php#L414 "Oro\Bundle\ActivityListBundle\Entity\Manager\ActivityListManager")</sup>

ApiBundle
---------
* The `SecurityFirewallContextListener::__construct(ListenerInterface $innerListener, string $sessionName, TokenStorageInterface $tokenStorage)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.2/src/Oro/Bundle/ApiBundle/EventListener/SecurityFirewallContextListener.php#L34 "Oro\Bundle\ApiBundle\EventListener\SecurityFirewallContextListener")</sup> method was changed to `SecurityFirewallContextListener::__construct(ListenerInterface $innerListener, array $sessionOptions, TokenStorageInterface $tokenStorage)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.3/src/Oro/Bundle/ApiBundle/EventListener/SecurityFirewallContextListener.php#L34 "Oro\Bundle\ApiBundle\EventListener\SecurityFirewallContextListener")</sup>
* The `SecurityFirewallExceptionListener::setSessionName`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.2/src/Oro/Bundle/ApiBundle/EventListener/SecurityFirewallExceptionListener.php#L21 "Oro\Bundle\ApiBundle\EventListener\SecurityFirewallExceptionListener::setSessionName")</sup> method was removed.

