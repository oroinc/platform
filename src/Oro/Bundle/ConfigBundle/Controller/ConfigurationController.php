<?php

namespace Oro\Bundle\ConfigBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
     *      label="oro.config.module_label",
     *      group_name=""
     * )
     */
    public function systemAction($activeGroup = null, $activeSubGroup = null)
    {
        $provider = $this->get('oro_config.provider.system_configuration.form_provider');

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);

        $tree = $provider->getTree();
        $form = false;
        if ($activeSubGroup !== null) {
            $form = $provider->getForm($activeSubGroup);

            if ($this->get('oro_config.form.handler.config')->process($form, $this->getRequest())) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.config.controller.config.saved.message')
                );

                // outdate content tags, it's only special case for generation that are not covered by NavigationBundle
                $tagableData = ['name' => 'system_configuration', 'params' => [$activeGroup, $activeSubGroup]];
                $sender      = $this->get('oro_navigation.content.topic_sender');
                $sender->send($sender->getGenerator()->generate($tagableData));

                return new RedirectResponse(
                    $this->get('router')->generate(
                        'oro_config_configuration_system',
                        ['activeGroup' => $activeGroup, 'activeSubGroup' => $activeSubGroup]
                    )
                );
            }
        }

        return array(
            'data'           => $tree,
            'form'           => $form ? $form->createView() : null,
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup,
        );
    }
}
