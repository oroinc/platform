<?php

namespace Oro\Bundle\AttachmentBundle\Layout\DataProvider;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileApplicationsProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Layout data provider for checking if field is allowed to display on the current application.
 */
class FileApplicationsDataProvider
{
    /** @var FileApplicationsProvider */
    private $fileApplicationsProvider;

    /** @var CurrentApplicationProviderInterface */
    private $currentApplicationProvider;

    /** @var ConfigProvider */
    private $configProvider;

    public function __construct(
        FileApplicationsProvider $fileApplicationsProvider,
        CurrentApplicationProviderInterface $currentApplicationProvider
    ) {
        $this->fileApplicationsProvider = $fileApplicationsProvider;
        $this->currentApplicationProvider = $currentApplicationProvider;
    }

    public function setConfigProvider(ConfigProvider $configProvider): void
    {
        $this->configProvider = $configProvider;
    }

    public function isValidForField(string $className, string $fieldName): bool
    {
        if ($this->configProvider) {
            $attachmentConfig = $this->configProvider->getConfig($className, $fieldName);

            $isValid = !$attachmentConfig->is('acl_protected') ||
                $this->currentApplicationProvider->isApplicationsValid(
                    $this->fileApplicationsProvider->getFileApplicationsForField($className, $fieldName)
                );
        } else {
            $isValid = $this->currentApplicationProvider->isApplicationsValid(
                $this->fileApplicationsProvider->getFileApplicationsForField($className, $fieldName)
            );
        }

        return $isValid;
    }
}
