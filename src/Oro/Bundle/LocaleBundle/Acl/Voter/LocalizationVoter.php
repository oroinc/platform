<?php

namespace Oro\Bundle\LocaleBundle\Acl\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Prevents removal of the default localization and the last localization that exists in the system.
 */
class LocalizationVoter extends AbstractEntityVoter implements ServiceSubscriberInterface
{
    protected $supportedAttributes = [BasicPermission::DELETE];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        private readonly ContainerInterface $container
    ) {
        parent::__construct($doctrineHelper);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ConfigManager::class
        ];
    }

    #[\Override]
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        return $this->getDefaultLocalizationId() === $identifier || $this->isLastLocalization()
            ? self::ACCESS_DENIED
            : self::ACCESS_ABSTAIN;
    }

    private function getDefaultLocalizationId(): int
    {
        return (int)$this->getConfigManager()->get(
            Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION)
        );
    }

    private function isLastLocalization(): bool
    {
        return $this->getLocalizationRepository()->getLocalizationsCount() <= 1;
    }

    private function getLocalizationRepository(): LocalizationRepository
    {
        return $this->doctrineHelper->getEntityRepository($this->className);
    }

    private function getConfigManager(): ConfigManager
    {
        return $this->container->get(ConfigManager::class);
    }
}
