<?php

namespace Oro\Bundle\ConfigBundle\Controller;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Oro\Bundle\ConfigBundle\Provider\AbstractProvider;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SyncBundle\Content\DataUpdateTopicSender;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;
use Psr\Container\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller to handle the system configuration.
 */
class ConfigurationController implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @Route(
     *      "/system/{activeGroup}/{activeSubGroup}",
     *      name="oro_config_configuration_system",
     *      defaults={"activeGroup" = null, "activeSubGroup" = null}
     * )
     * @Template()
     * @Acl(
     *      id="oro_config_system",
     *      type="action",
     *      label="oro.config.acl.action.general.label",
     *      group_name="",
     *      category="application"
     * )
     */
    public function systemAction(Request $request, ?string $activeGroup = null, ?string $activeSubGroup = null): array
    {
        $provider = $this->getConfigFormProvider();
        [$activeGroup, $activeSubGroup] = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);
        $form = null;
        if (null !== $activeSubGroup) {
            $manager = $this->getConfigManager();
            $form = $provider->getForm($activeSubGroup, $manager);
            if ($this->getConfigHandler()->process($form, $request)) {
                $request->getSession()->getFlashBag()->add(
                    'success',
                    $this->getTranslator()->trans('oro.config.controller.config.saved.message')
                );

                // outdate content tags, it's only special case for generation that are not covered by NavigationBundle
                $this->getDataUpdateTopicSender()->send($this->getTagGenerator()->generate([
                    'name'   => 'system_configuration',
                    'params' => [$activeGroup, $activeSubGroup]
                ]));

                // recreate form to drop values for fields with use_parent_scope_value
                $form = $provider->getForm($activeSubGroup, $manager);
                $form->setData($manager->getSettingsByForm($form));
            }
        }

        return [
            'data'             => $provider->getJsTree(),
            'form'             => $form?->createView(),
            'activeGroup'      => $activeGroup,
            'activeSubGroup'   => $activeSubGroup,
            'scopeEntity'      => null,
            'scopeEntityClass' => null,
            'scopeEntityId'    => null
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            SystemConfigurationFormProvider::class,
            ConfigManager::class,
            ConfigHandler::class,
            TranslatorInterface::class,
            TagGeneratorInterface::class,
            DataUpdateTopicSender::class,
        ];
    }

    private function getConfigFormProvider(): AbstractProvider
    {
        return $this->container->get(SystemConfigurationFormProvider::class);
    }

    private function getConfigManager(): ConfigManager
    {
        return $this->container->get(ConfigManager::class);
    }

    private function getConfigHandler(): ConfigHandler
    {
        return $this->container->get(ConfigHandler::class);
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->container->get(TranslatorInterface::class);
    }

    private function getTagGenerator(): TagGeneratorInterface
    {
        return $this->container->get(TagGeneratorInterface::class);
    }

    private function getDataUpdateTopicSender(): DataUpdateTopicSender
    {
        return $this->container->get(DataUpdateTopicSender::class);
    }
}
