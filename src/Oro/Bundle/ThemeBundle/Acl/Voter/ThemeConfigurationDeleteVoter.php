<?php

namespace Oro\Bundle\ThemeBundle\Acl\Voter;

use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\ThemeBundle\DependencyInjection\Configuration;

/**
 * Prevents removal of theme configurations that are in use.
 */
class ThemeConfigurationDeleteVoter extends AbstractEntityVoter
{
    protected $supportedAttributes = [BasicPermission::DELETE];

    #[\Override]
    protected function getPermissionForAttribute($class, $identifier, $attribute): int
    {
        if ($this->isSelectedInSystemConfiguration($identifier)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    private function isSelectedInSystemConfiguration(int $identifier): bool
    {
        return (bool)$this->doctrineHelper
            ->getEntityRepository(ConfigValue::class)
            ->findBy([
                'section' => Configuration::ROOT_NAME,
                'name' => Configuration::THEME_CONFIGURATION,
                'textValue' => $identifier
            ]);
    }
}
