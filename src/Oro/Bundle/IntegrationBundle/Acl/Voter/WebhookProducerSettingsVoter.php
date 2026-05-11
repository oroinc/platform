<?php

namespace Oro\Bundle\IntegrationBundle\Acl\Voter;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Prevents removal of system WebhookProducerSettings entries.
 */
class WebhookProducerSettingsVoter extends AbstractEntityVoter
{
    protected $supportedAttributes = [BasicPermission::DELETE];

    private mixed $object;

    #[\Override]
    public function vote(TokenInterface $token, $object, array $attributes): int
    {
        $this->object = $object;
        try {
            return parent::vote($token, $object, $attributes);
        } finally {
            $this->object = null;
        }
    }

    #[\Override]
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        return $this->object instanceof WebhookProducerSettings && $this->object->isSystem()
            ? self::ACCESS_DENIED
            : self::ACCESS_ABSTAIN;
    }
}
