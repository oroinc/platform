<?php

namespace Oro\Bundle\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\UserBundle\Entity\User;

class ConfigurationController extends Controller
{
    /**
     * @Route(
     *      "/user/{id}/{activeGroup}/{activeSubGroup}",
     *      name="oro_user_config",
     *      requirements={"id"="\d+"},
     *      defaults={"activeGroup" = null, "activeSubGroup" = null}
     * )
     * @Template()
     * @AclAncestor("oro_user_user_update")
     *
     * @param User $entity
     * @param null $activeGroup
     * @param null $activeSubGroup
     * @return array
     */
    public function userConfigAction(User $entity, $activeGroup = null, $activeSubGroup = null)
    {
        $result = $this->config($entity, $activeGroup, $activeSubGroup);
        $result['routeName'] = 'oro_user_config';
        $result['routeParameters'] = ['id' => $entity->getId()];

        return $result;
    }

    /**
     * @Route("/user/profile/{activeGroup}/{activeSubGroup}", name="oro_user_profile_configuration")
     * @Template("OroUserBundle:Configuration:userConfig.html.twig")
     *
     * @param null $activeGroup
     * @param null $activeSubGroup
     * @return array
     */
    public function userProfileConfigAction($activeGroup = null, $activeSubGroup = null)
    {
        $result = $this->config($this->getUser(), $activeGroup, $activeSubGroup);
        $result['routeName'] = 'oro_user_profile_configuration';
        $result['routeParameters'] = [];

        return $result;
    }

    /**
     * @param User $entity
     * @param string|null $activeGroup
     * @param string|null $activeSubGroup
     * @return array
     */
    protected function config(User $entity, $activeGroup = null, $activeSubGroup = null)
    {
        $provider = $this->get('oro_user.provider.user_config_form_provider');

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);

        $tree = $provider->getTree();
        $form = false;

        if ($activeSubGroup !== null) {
            $form = $provider->getForm($activeSubGroup);

            $manager = $this->get('oro_config.user');
            $prevScopeId = $manager->getScopeId();
            //update scope id to match currently configured user
            $manager->setScopeIdFromEntity($entity);

            if ($this->get('oro_config.form.handler.config')
                ->setConfigManager($manager)
                ->process($form, $this->getRequest())
            ) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.config.controller.config.saved.message')
                );

                // outdate content tags, it's only special case for generation that are not covered by NavigationBundle
                $taggableData = ['name' => 'user_configuration', 'params' => [$activeGroup, $activeSubGroup]];
                $sender       = $this->get('oro_navigation.content.topic_sender');

                $sender->send($sender->getGenerator()->generate($taggableData));
            }
            //revert previous scope id
            $manager->setScopeId($prevScopeId);
        }

        return [
            'entity'         => $entity,
            'data'           => $tree,
            'form'           => $form ? $form->createView() : null,
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup,
        ];
    }

    /**
     * @Route(
     *      "/user/emailsettings/{id}/{activeGroup}/{activeSubGroup}",
     *      requirements={"id"="\d+"},
     *      name="oro_user_config_emailsettings"
     * )
     * @Template
     * @AclAncestor("oro_user_user_update")
     *
     * @param User $user
     * @param string|null $activeGroup
     * @param string|null $activeSubGroup
     * @return array
     */
    public function emailSettingsAction(User $user, $activeGroup = null, $activeSubGroup = null)
    {
        return $this->emailSettings($user, 'oro_user_config_emailsettings', $activeGroup, $activeSubGroup);
    }

    /**
     * @Route(
     *      "/user/profile/emailsettings/{activeGroup}/{activeSubGroup}",
     *      requirements={"id"="\d+"},
     *      name="oro_user_profile_config_emailsettings"
     * )
     * @Template("OroUserBundle:Configuration:emailSettings.html.twig")
     *
     * @param string|null $activeGroup
     * @param string|null $activeSubGroup
     * @return array
     */
    public function profileEmailSettingsAction($activeGroup = null, $activeSubGroup = null)
    {
        return $this->emailSettings(
            $this->getUser(),
            'oro_user_profile_config_emailsettings',
            $activeGroup,
            $activeSubGroup
        );
    }

    /**
     * @param User $user
     * @param string $route
     * @param string|null $activeGroup
     * @param string|null $activeSubGroup
     * @return array
     */
    protected function emailSettings(User $user, $route, $activeGroup = null, $activeSubGroup = null)
    {
        $provider = $this->get('oro_user.provider.user_config_form_provider');

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);

        $tree = $provider->getTree();
        if ('oro_user_config_emailsettings' == $route) {
            $redirectRoute = 'oro_user_config';
            $redirectParams = ['id' => $user->getId()];
        } else {
            $redirectRoute = 'oro_user_profile_configuration';
            $redirectParams = [];
        }
         
        $redirectData = [
            'route' => $redirectRoute,
            'parameters' => $redirectParams + [
                'activeGroup' => $activeGroup,
                'activeSubGroup' => $activeSubGroup
            ]
        ];

        $handler = $this->get('oro_user.form.handler.user_emailsettings');

        if ($handler->process($user)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.config.controller.config.saved.message')
            );

            return $this->get('oro_ui.router')->redirectAfterSave(
                [
                    'route' => $route,
                    'parameters' => $redirectParams
                ],
                $redirectData,
                $user
            );
        }
        
        return [
            'entity'          => $user,
            'data'            => $tree,
            'form'            => $handler->getForm()->createView(),
            'activeGroup'     => $activeGroup,
            'activeSubGroup'  => $activeSubGroup,
            'routeName'       => $redirectRoute,
            'routeParameters' => $redirectParams
        ];
    }
}
