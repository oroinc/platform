<?php

namespace Oro\Bundle\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
     * @Acl(
     *      id="oro_user_user_config",
     *      type="entity",
     *      class="OroUserBundle:User",
     *      permission="CONFIGURE"
     * )
     *
     * @param User $entity
     * @param null $activeGroup
     * @param null $activeSubGroup
     * @return array
     */
    public function userConfigAction(User $entity, $activeGroup = null, $activeSubGroup = null)
    {

        if (!$this->get('oro_security.security_facade')->isGranted('update_own_configuration')) {
            throw new AccessDeniedException();
        }

        $result = $this->config($entity, $activeGroup, $activeSubGroup);
        $result['routeName'] = 'oro_user_config';
        $result['routeParameters'] = ['id' => $entity->getId()];

        return $result;
    }

    /**
     * @Route("/user/profile/{activeGroup}/{activeSubGroup}", name="oro_user_profile_configuration")
     * @Template("OroUserBundle:Configuration:userConfig.html.twig")
     * @AclAncestor("update_own_configuration")
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
        $manager = $this->get('oro_config.user');

        if ($activeSubGroup !== null) {
            $form = $provider->getForm($activeSubGroup);

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
            'scopeInfo'      => $manager->getScopeInfo()
        ];
    }
}
