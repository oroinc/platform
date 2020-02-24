<?php

namespace Oro\Bundle\AttachmentBundle\Layout\DataProvider;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileApplicationsProvider;

/**
 * Layout data provider for checking if field is allowed to display on the current application.
 */
class FileApplicationsDataProvider
{
    /** @var FileApplicationsProvider */
    private $fileApplicationsProvider;

    /** @var CurrentApplicationProviderInterface */
    private $currentApplicationProvider;

    /**
     * @param FileApplicationsProvider $fileApplicationsProvider
     * @param CurrentApplicationProviderInterface $currentApplicationProvider
     */
    public function __construct(
        FileApplicationsProvider $fileApplicationsProvider,
        CurrentApplicationProviderInterface $currentApplicationProvider
    ) {
        $this->fileApplicationsProvider = $fileApplicationsProvider;
        $this->currentApplicationProvider = $currentApplicationProvider;
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    public function isValidForField(string $className, string $fieldName): bool
    {
        return $this->currentApplicationProvider->isApplicationsValid(
            $this->fileApplicationsProvider->getFileApplicationsForField($className, $fieldName)
        );
    }
}
