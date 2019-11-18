<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SyncBundle\Content\DataUpdateTopicSender;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\UserConfigurationFormProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides actions to configure user profiles.
 */
class ConfigurationController extends AbstractController
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
        $provider = $this->get(UserConfigurationFormProvider::class);
        /** @var ConfigManager $manager */
        $manager = $this->get('oro_config.user');
        $prevScopeId = $manager->getScopeId();
        //update scope id to match currently configured user
        $manager->setScopeIdFromEntity($entity);

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);

        $jsTree = $provider->getJsTree();
        $form = false;

        if ($activeSubGroup !== null) {
            $form = $provider->getForm($activeSubGroup);

            if ($this->get(ConfigHandler::class)
                ->setConfigManager($manager)
                ->process($form, $this->get(RequestStack::class)->getCurrentRequest())
            ) {
                $this->get(SessionInterface::class)->getFlashBag()->add(
                    'success',
                    $this->get(TranslatorInterface::class)->trans('oro.config.controller.config.saved.message')
                );

                // outdate content tags, it's only special case for generation that are not covered by NavigationBundle
                $taggableData = ['name' => 'user_configuration', 'params' => [$activeGroup, $activeSubGroup]];
                $tagGenerator = $this->get(TagGeneratorInterface::class);
                $dataUpdateTopicSender = $this->get(DataUpdateTopicSender::class);

                $dataUpdateTopicSender->send($tagGenerator->generate($taggableData));
            }
        }
        //revert previous scope id
        $manager->setScopeId($prevScopeId);

        return [
            'entity'         => $entity,
            'data'           => $jsTree,
            'form'           => $form ? $form->createView() : null,
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup,
            'scopeInfo'      => $manager->getScopeInfo(),
            'scopeEntity'    => $entity,
            'scopeEntityClass' => User::class,
            'scopeEntityId'  => $entity->getId()
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
                'oro_config.user' => ConfigManager::class,
                RequestStack::class,
                SessionInterface::class,
                TranslatorInterface::class,
                TagGeneratorInterface::class,
                UserConfigurationFormProvider::class,
                DataUpdateTopicSender::class,
                ConfigHandler::class,
            ]
        );
    }
}
