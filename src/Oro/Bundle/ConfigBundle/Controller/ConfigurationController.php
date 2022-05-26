<?php

namespace Oro\Bundle\ConfigBundle\Controller;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SyncBundle\Content\DataUpdateTopicSender;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for system config functionality
 */
class ConfigurationController extends AbstractController
{
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
     * @param Request $request
     * @param mixed $activeGroup
     * @param mixed $activeSubGroup
     * @return array
     */
    public function systemAction(Request $request, $activeGroup = null, $activeSubGroup = null)
    {
        $provider = $this->get(SystemConfigurationFormProvider::class);

        [$activeGroup, $activeSubGroup] = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);

        $jsTree = $provider->getJsTree();
        $form = false;

        if ($activeSubGroup !== null) {
            $manager = $this->get(ConfigManager::class);
            $form = $provider->getForm($activeSubGroup, $manager);

            if ($this->get(ConfigHandler::class)->process($form, $request)) {
                $request->getSession()->getFlashBag()->add(
                    'success',
                    $this->get(TranslatorInterface::class)->trans('oro.config.controller.config.saved.message')
                );

                // outdate content tags, it's only special case for generation that are not covered by NavigationBundle
                $taggableData = ['name' => 'system_configuration', 'params' => [$activeGroup, $activeSubGroup]];
                $tagGenerator = $this->get(TagGeneratorInterface::class);
                $dataUpdateTopicSender = $this->get(DataUpdateTopicSender::class);

                $dataUpdateTopicSender->send($tagGenerator->generate($taggableData));

                // recreate form to drop values for fields with use_parent_scope_value
                $form = $provider->getForm($activeSubGroup, $manager);
                $form->setData($manager->getSettingsByForm($form));
            }
        }

        return [
            'data'           => $jsTree,
            'form'           => $form ? $form->createView() : null,
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup,
            'scopeEntity'    => null,
            'scopeEntityClass' => null,
            'scopeEntityId'  => null
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                SystemConfigurationFormProvider::class,
                ConfigHandler::class,
                TagGeneratorInterface::class,
                DataUpdateTopicSender::class,
                ConfigManager::class,
            ]
        );
    }
}
