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

    /** @var array */
    protected $supportedAttributes = [
        BasicPermission::DELETE,
        self::EMAIL_TEMPLATE_DELETE_ALIAS
    ];

    /** @var EmailTemplate */
    private $object;

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $this->object = $object;

        return parent::vote($token, $object, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->isDeleteDenied($attribute)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @param string $attribute
     *
     * @return bool
     */
    private function isDeleteDenied($attribute)
    {
        return
            in_array($attribute, $this->supportedAttributes, true)
            && $this->object instanceof EmailTemplate
            && $this->object->getIsSystem();
    }
}
