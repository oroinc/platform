<?php

namespace Oro\Bundle\LocaleBundle\Acl\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

/**
 * Prevents removal of the default localization and the last localization that exists in the system.
 */
class LocalizationVoter extends AbstractEntityVoter
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var array
     */
    protected $supportedAttributes = [BasicPermission::DELETE];

    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        parent::__construct($doctrineHelper);
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        $id = (int)$this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION));
        if ($id == $identifier || $this->isLastLocalization()) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @return bool
     */
    protected function isLastLocalization()
    {
        /** @var LocalizationRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository($this->className);

        $localizationsCount = $repository->getLocalizationsCount();
        return $localizationsCount <= 1;
    }
}
