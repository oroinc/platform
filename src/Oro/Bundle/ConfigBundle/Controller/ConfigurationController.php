<?php

namespace Oro\Bundle\ConfigBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ConfigurationController extends Controller
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
        $provider = $this->get('oro_config.provider.system_configuration.form_provider');

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);

        $jsTree = $provider->getJsTree();
        $form = false;

        if ($activeSubGroup !== null) {
            $form = $provider->getForm($activeSubGroup);

            if ($this->get('oro_config.form.handler.config')->process($form, $request)) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.config.controller.config.saved.message')
                );

                // outdate content tags, it's only special case for generation that are not covered by NavigationBundle
                $taggableData = ['name' => 'system_configuration', 'params' => [$activeGroup, $activeSubGroup]];
                $tagGenerator = $this->get('oro_sync.content.tag_generator');
                $dataUpdateTopicSender = $this->get('oro_sync.content.data_update_topic_sender');

                $dataUpdateTopicSender->send($tagGenerator->generate($taggableData));

                // recreate form to drop values for fields with use_parent_scope_value
                $form = $provider->getForm($activeSubGroup);
                $form->setData($this->get('oro_config.global')->getSettingsByForm($form));
            }
        }

        return [
            'data'           => $jsTree,
            'form'           => $form ? $form->createView() : null,
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup
        ];
    }
}
