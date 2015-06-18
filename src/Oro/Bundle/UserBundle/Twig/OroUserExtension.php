<?php

namespace Oro\Bundle\UserBundle\Twig;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\UserBundle\Provider\GenderProvider;
use Oro\Bundle\UserBundle\Security\AdvancedApiUserInterface;

class OroUserExtension extends \Twig_Extension
{
    /** @var GenderProvider */
    protected $genderProvider;

    /** @var SecurityContextInterface */
    protected $securityContext;

    /**
     * @param GenderProvider           $genderProvider
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(GenderProvider $genderProvider, SecurityContextInterface $securityContext)
    {
        $this->genderProvider  = $genderProvider;
        $this->securityContext = $securityContext;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            'oro_gender'       => new \Twig_SimpleFunction('oro_gender', [$this, 'getGenderLabel']),
            'get_current_user' => new \Twig_SimpleFunction('get_current_user', [$this, 'getCurrentUser'])
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

        return $this->genderProvider->getLabelByName($name);
    }

    /**
     * Returns currently logged in user
     *
     * @return AdvancedApiUserInterface|null
     */
    public function getCurrentUser()
    {
        $token = $this->securityContext->getToken();
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
