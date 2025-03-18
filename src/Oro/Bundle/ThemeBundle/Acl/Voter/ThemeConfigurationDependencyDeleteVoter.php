<?php

namespace Oro\Bundle\ThemeBundle\Acl\Voter;

use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;

/**
 * Disables removal for dependent entities if it was used in {@see ThemeConfiguration}.
 */
class ThemeConfigurationDependencyDeleteVoter extends AbstractEntityVoter
{
    protected $supportedAttributes = [BasicPermission::DELETE];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        private ThemeConfigurationProvider $configurationProvider,
        private string $themeConfigType
    ) {
        parent::__construct($doctrineHelper);
        $this->setClassName(ContentBlock::class);
    }

    #[\Override]
    protected function getPermissionForAttribute($class, $identifier, $attribute): int
    {
        if (empty($identifier)) {
            return self::ACCESS_ABSTAIN;
        }

        $optionsNames = $this->configurationProvider->getThemeConfigurationOptionsNamesByType($this->themeConfigType);
        $configuration = $this->configurationProvider->getThemeConfigurationOptions();

        foreach ($optionsNames as $optionName) {
            if (($configuration[$optionName] ?? null) === $identifier) {
                return self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
