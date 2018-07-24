<?php

namespace Oro\Bundle\EmailBundle\Acl\Voter;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Security voter that prevents removal of the "system" email template
 */
class EmailTemplateVoter extends AbstractEntityVoter
{
    const EMAIL_TEMPLATE_DELETE_ALIAS = 'oro_email_emailtemplate_delete';

    /** @var EmailTemplate */
    private $object;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        parent::__construct($doctrineHelper);

        $this->supportedAttributes = [
            BasicPermissionMap::PERMISSION_DELETE,
            self::EMAIL_TEMPLATE_DELETE_ALIAS
        ];
    }

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
        return in_array($attribute, [BasicPermissionMap::PERMISSION_DELETE, self::EMAIL_TEMPLATE_DELETE_ALIAS], true)
            && $this->object instanceof EmailTemplate
            && $this->object->getIsSystem();
    }
}
