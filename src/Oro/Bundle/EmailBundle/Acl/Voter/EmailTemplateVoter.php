<?php

namespace Oro\Bundle\EmailBundle\Acl\Voter;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Prevents removal of the "system" email templates.
 */
class EmailTemplateVoter extends AbstractEntityVoter
{
    const EMAIL_TEMPLATE_DELETE_ALIAS = 'oro_email_emailtemplate_delete';

    /** {@inheritDoc} */
    protected $supportedAttributes = [
        BasicPermission::DELETE,
        self::EMAIL_TEMPLATE_DELETE_ALIAS
    ];

    private mixed $object;

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $this->object = $object;
        try {
            return parent::vote($token, $object, $attributes);
        } finally {
            $this->object = null;
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        return $this->isDeleteDenied($attribute)
            ? self::ACCESS_DENIED
            : self::ACCESS_ABSTAIN;
    }

    private function isDeleteDenied(string $attribute): bool
    {
        return
            \in_array($attribute, $this->supportedAttributes, true)
            && $this->object instanceof EmailTemplate
            && $this->object->getIsSystem();
    }
}
