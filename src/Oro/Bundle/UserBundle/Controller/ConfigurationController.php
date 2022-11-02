<?php

namespace Oro\Bundle\UserBundle\Controller;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Form\Handler\ConfigHandler;
use Oro\Bundle\ConfigBundle\Provider\AbstractProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SyncBundle\Content\DataUpdateTopicSender;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\UserConfigurationFormProvider;
use Psr\Container\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller to handle the user configuration.
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
     */
    public function userConfigAction(
        User $entity,
        Request $request,
        ?string $activeGroup = null,
        ?string $activeSubGroup = null
    ): array {
        $result = $this->config($entity, $request, $activeGroup, $activeSubGroup);
        $result['routeName'] = 'oro_user_config';
        $result['routeParameters'] = ['id' => $entity->getId()];

        return $result;
    }

    /**
     * @Route("/user/profile/{activeGroup}/{activeSubGroup}", name="oro_user_profile_configuration")
     * @Template("@OroUser/Configuration/userConfig.html.twig")
     * @AclAncestor("update_own_configuration")
     */
    public function userProfileConfigAction(
        Request $request,
        ?string $activeGroup = null,
        ?string $activeSubGroup = null
    ): array {
        $result = $this->config($this->getUser(), $request, $activeGroup, $activeSubGroup);
        $result['routeName'] = 'oro_user_profile_configuration';
        $result['routeParameters'] = [];

        return $result;
    }

    private function config(User $entity, Request $request, ?string $activeGroup, ?string $activeSubGroup): array
    {
        $provider = $this->getConfigFormProvider();
        $manager = $this->getConfigManager();
        $prevScopeId = $manager->getScopeId();
        $manager->setScopeIdFromEntity($entity);
        [$activeGroup, $activeSubGroup] = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);
        $form = null;
        if (null !== $activeSubGroup) {
            $form = $provider->getForm($activeSubGroup, $manager);
            if ($this->getConfigHandler()->setConfigManager($manager)->process($form, $request)) {
                $request->getSession()->getFlashBag()->add(
                    'success',
                    $this->getTranslator()->trans('oro.config.controller.config.saved.message')
                );

                // outdate content tags, it's only special case for generation that are not covered by NavigationBundle
                $this->getDataUpdateTopicSender()->send($this->getTagGenerator()->generate([
                    'name'   => 'user_configuration',
                    'params' => [$activeGroup, $activeSubGroup]
                ]));
            }
        }
        $manager->setScopeId($prevScopeId);

        return [
            'entity'           => $entity,
            'data'             => $provider->getJsTree(),
            'form'             => $form?->createView(),
            'activeGroup'      => $activeGroup,
            'activeSubGroup'   => $activeSubGroup,
            'scopeInfo'        => $manager->getScopeInfo(),
            'scopeEntity'      => $entity,
            'scopeEntityClass' => User::class,
            'scopeEntityId'    => $entity->getId()
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            UserConfigurationFormProvider::class,
            ConfigManager::class,
            ConfigHandler::class,
            TranslatorInterface::class,
            TagGeneratorInterface::class,
            DataUpdateTopicSender::class,
            TokenStorageInterface::class
        ];
    }

    private function getConfigFormProvider(): AbstractProvider
    {
        return $this->container->get(UserConfigurationFormProvider::class);
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

    private function getTokenStorage(): TokenStorageInterface
    {
        return $this->container->get(TokenStorageInterface::class);
    }

    protected function getUser(): User
    {
        $token = $this->getTokenStorage()->getToken();
        if (null === $token) {
            throw new \LogicException('No security token.');
        }
        $user = $token->getUser();
        if (null === $user) {
            throw new \LogicException('No current user.');
        }
        if (!$user instanceof User) {
            throw new \LogicException('Unexpected type of current user.');
        }

        return $user;
    }
}
