<?php

namespace Oro\Bundle\UserBundle\Twig;

use Oro\Bundle\UserBundle\Provider\GenderProvider;
use Oro\Bundle\UserBundle\Security\AdvancedApiUserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class OroUserExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return GenderProvider
     */
    protected function getGenderProvider()
    {
        return $this->container->get('oro_user.gender_provider');
    }

    /**
     * @return TokenStorageInterface
     */
    protected function getSecurityTokenStorage()
    {
        return $this->container->get('security.token_storage');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_gender', [$this, 'getGenderLabel']),
            new \Twig_SimpleFunction('get_current_user', [$this, 'getCurrentUser'])
        ];
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getGenderLabel($name)
    {
        if (!$name) {
            return null;
        }

        return $this->getGenderProvider()->getLabelByName($name);
    }

    /**
     * Returns currently logged in user
     *
     * @return AdvancedApiUserInterface|null
     */
    public function getCurrentUser()
    {
        $token = $this->getSecurityTokenStorage()->getToken();
        if (!$token) {
            return null;
        }
        $user = $token->getUser();
        if (!$user instanceof AdvancedApiUserInterface) {
            return null;
        }

        return $user;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'user_extension';
    }
}
