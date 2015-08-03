<?php

namespace Oro\Bundle\SecurityBundle\Twig;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Symfony\Component\Translation\TranslatorInterface;

class OroSecurityExtension extends \Twig_Extension
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade, TranslatorInterface $translator)
    {
        $this->securityFacade = $securityFacade;
        $this->translator = $translator;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'resource_granted' => new \Twig_Function_Method($this, 'checkResourceIsGranted'),
            'format_share_scopes' => new \Twig_Function_Method($this, 'formatShareScopes'),
        );
    }

    /**
     * Check if ACL resource is granted for current user
     *
     * @param string|string[] $attributes Can be a role name(s), permission name(s), an ACL annotation id
     *                                    or something else, it depends on registered security voters
     * @param mixed $object A domain object, object identity or object identity descriptor (id:type)
     *
     * @return bool
     */
    public function checkResourceIsGranted($attributes, $object = null)
    {
        return $this->securityFacade->isGranted($attributes, $object);
    }

    /**
     * Formats json encoded string of share scopes entity config attribute
     *
     * @param string|array|null $value
     * @param string $labelType
     *
     * @return string
     */
    public function formatShareScopes($value, $labelType = 'label')
    {
        if (!$value) {
            return $this->translator->trans('oro.security.share_scopes.not_available');
        }
        $result = [];
        if (is_string($value)) {
            $shareScopes = json_decode($value);
        } elseif (is_array($value)) {
            $shareScopes = $value;
        } else {
            throw new \LogicException('$value must be string or array');
        }

        foreach ($shareScopes as $shareScope) {
            $result[] = $this->translator->trans('oro.security.share_scopes.' . $shareScope . '.' . $labelType);
        }

        return implode(', ', $result);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'oro_security_extension';
    }
}
