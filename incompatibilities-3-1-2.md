- [EmailBundle](#emailbundle)
- [IntegrationBundle](#integrationbundle)
- [ReportBundle](#reportbundle)
- [RequireJSBundle](#requirejsbundle)
- [SecurityBundle](#securitybundle)
- [UserBundle](#userbundle)
- [WorkflowBundle](#workflowbundle)

EmailBundle
-----------
* The `EmailConfigurationConfigurator::__construct(SymmetricCrypterInterface $encryptor)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.1/src/Oro/Bundle/EmailBundle/Form/Configurator/EmailConfigurationConfigurator.php#L23 "Oro\Bundle\EmailBundle\Form\Configurator\EmailConfigurationConfigurator")</sup> method was changed to `EmailConfigurationConfigurator::__construct(SymmetricCrypterInterface $encryptor, ValidatorInterface $validator)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.2/src/Oro/Bundle/EmailBundle/Form/Configurator/EmailConfigurationConfigurator.php#L30 "Oro\Bundle\EmailBundle\Form\Configurator\EmailConfigurationConfigurator")</sup>

IntegrationBundle
-----------------
* The `ChannelHandler::__construct(RequestStack $requestStack, FormInterface $form, EntityManager $em, EventDispatcherInterface $eventDispatcher)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.1/src/Oro/Bundle/IntegrationBundle/Form/Handler/ChannelHandler.php#L38 "Oro\Bundle\IntegrationBundle\Form\Handler\ChannelHandler")</sup> method was changed to `ChannelHandler::__construct(RequestStack $requestStack, FormInterface $form, EntityManager $em, EventDispatcherInterface $eventDispatcher, FormFactoryInterface $formFactory)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.2/src/Oro/Bundle/IntegrationBundle/Form/Handler/ChannelHandler.php#L51 "Oro\Bundle\IntegrationBundle\Form\Handler\ChannelHandler")</sup>
* The `IntegrationController::getForm`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.1/src/Oro/Bundle/IntegrationBundle/Controller/IntegrationController.php#L152 "Oro\Bundle\IntegrationBundle\Controller\IntegrationController::getForm")</sup> method was removed.

ReportBundle
------------
* The `OroReportExtension::getAlias`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.1/src/Oro/Bundle/ReportBundle/DependencyInjection/OroReportExtension.php#L37 "Oro\Bundle\ReportBundle\DependencyInjection\OroReportExtension::getAlias")</sup> method was removed.

RequireJSBundle
---------------
* The `OroBuildCommand::__construct(NodeProcessFactory $nodeProcessFactory, ConfigProviderManager $configProviderManager, Filesystem $filesystem, string $webRoot, $timeout)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.1/src/Oro/Bundle/RequireJSBundle/Command/OroBuildCommand.php#L53 "Oro\Bundle\RequireJSBundle\Command\OroBuildCommand")</sup> method was changed to `OroBuildCommand::__construct(NodeProcessFactory $nodeProcessFactory, ConfigProviderManager $configProviderManager, Filesystem $filesystem, string $webRoot, $timeout, CacheProvider $cache)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.2/src/Oro/Bundle/RequireJSBundle/Command/OroBuildCommand.php#L60 "Oro\Bundle\RequireJSBundle\Command\OroBuildCommand")</sup>

SecurityBundle
--------------
* The `ChainEntityOwnershipDecisionMaker::$ownershipDecisionMaker`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.1/src/Oro/Bundle/SecurityBundle/Owner/ChainEntityOwnershipDecisionMaker.php#L22 "Oro\Bundle\SecurityBundle\Owner\ChainEntityOwnershipDecisionMaker::$ownershipDecisionMaker")</sup> property was removed.
* The `ChainAclGroupProvider::$supportedProvider`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.1/src/Oro/Bundle/SecurityBundle/Acl/Group/ChainAclGroupProvider.php#L17 "Oro\Bundle\SecurityBundle\Acl\Group\ChainAclGroupProvider::$supportedProvider")</sup> property was removed.
* The `EntitySecurityMetadataProvider::__construct(ConfigProvider $securityConfigProvider, ConfigProvider $entityConfigProvider, ConfigProvider $extendConfigProvider, ManagerRegistry $doctrine, TranslatorInterface $translator, CacheProvider $cache = null, EventDispatcherInterface $eventDispatcher)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.1/src/Oro/Bundle/SecurityBundle/Metadata/EntitySecurityMetadataProvider.php#L64 "Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider")</sup> method was changed to `EntitySecurityMetadataProvider::__construct(ConfigProvider $securityConfigProvider, ConfigProvider $entityConfigProvider, ConfigProvider $extendConfigProvider, ManagerRegistry $doctrine, TranslatorInterface $translator, CacheProvider $cache = null, EventDispatcherInterface $eventDispatcher, AclGroupProviderInterface $aclGroupProvider)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.2/src/Oro/Bundle/SecurityBundle/Metadata/EntitySecurityMetadataProvider.php#L69 "Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider")</sup>

UserBundle
----------
* The `UserImapConfigSubscriber::postSubmit`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.1/src/Oro/Bundle/UserBundle/Form/EventListener/UserImapConfigSubscriber.php#L56 "Oro\Bundle\UserBundle\Form\EventListener\UserImapConfigSubscriber::postSubmit")</sup> method was removed.

WorkflowBundle
--------------
* The `IsGrantedWorkflowTransition::__construct(AuthorizationCheckerInterface $authorizationChecker, TokenAccessorInterface $tokenAccessor)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.1/src/Oro/Bundle/WorkflowBundle/Model/Condition/IsGrantedWorkflowTransition.php#L43 "Oro\Bundle\WorkflowBundle\Model\Condition\IsGrantedWorkflowTransition")</sup> method was changed to `IsGrantedWorkflowTransition::__construct(AuthorizationCheckerInterface $authorizationChecker, TokenAccessorInterface $tokenAccessor, WorkflowManager $workflowManager)`<sup>[[?]](https://github.com/oroinc/platform/tree/3.1.2/src/Oro/Bundle/WorkflowBundle/Model/Condition/IsGrantedWorkflowTransition.php#L50 "Oro\Bundle\WorkflowBundle\Model\Condition\IsGrantedWorkflowTransition")</sup>

