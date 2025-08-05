<?php

namespace Oro\Bundle\SecurityBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\InstallerBundle\Provider\AbstractRequirementsProvider;
use Oro\Bundle\SecurityBundle\DependencyInjection\Configuration;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Requirements\RequirementCollection;

/**
 * This class is a requirements provider that checks if the symmetric crypter is configured correctly.
 */
class SymmetricCrypterCheckProvider extends AbstractRequirementsProvider
{
    public const string DEFAULT_SYSTEM_CHECK_CRYPTER_VALUE = 'default_symmetric_crypter_value';

    public function __construct(
        protected ConfigManager $configManager,
        protected ApplicationState $applicationState,
        protected SymmetricCrypterInterface $crypter
    ) {
    }

    #[\Override]
    public function getOroRequirements(): ?RequirementCollection
    {
        if (!$this->applicationState->isInstalled()) {
            return null;
        }
        $collection = new RequirementCollection();
        $checkCrypterKey = Configuration::getConfigKey(Configuration::SYSTEM_CHECK_CRYPTER_KEY);
        $encryptedKey = $this->getDefaultEncodedConfigKey($checkCrypterKey);
        $decryptedValue = null;
        $errorMessage = \sprintf('Failed to decrypt the key: %s', $checkCrypterKey);

        try {
            $decryptedValue = $this->crypter->decryptData($encryptedKey);
        } catch (\Exception $exception) {
            $errorMessage = \sprintf('%s. Exception: %s', $errorMessage, $exception->getMessage());
        }
        $collection->addRequirement(
            $decryptedValue === self::DEFAULT_SYSTEM_CHECK_CRYPTER_VALUE,
            'The symmetric cryptor is configured properly.',
            \sprintf(
                'The symmetric cryptor is not configured properly. '
                . 'Make sure the `ORO_SECRET` environment variable has not been modified. %s',
                $errorMessage
            ),
        );

        return $collection;
    }

    protected function getDefaultEncodedConfigKey(string $checkCrypterKey): string
    {
        $encryptedKey = $this->configManager->get($checkCrypterKey);
        if (!$encryptedKey) {
            $encryptedKey = $this->crypter->encryptData(self::DEFAULT_SYSTEM_CHECK_CRYPTER_VALUE);

            // set default values for configuration, this is needed to avoid migration
            $this->configManager->set($checkCrypterKey, $encryptedKey);
            $this->configManager->flush();
        }
        return $encryptedKey;
    }
}
