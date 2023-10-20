<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

/**
 * This class provides default values for the installation in the test environment
 */
class InstallDefaultOptionsProvider
{
    public function __construct(
        private array $installOptions
    ) {
    }

    public function getUserName(): ?string
    {
        return $this->installOptions['user_name'];
    }

    public function getUserEmail(): ?string
    {
        return $this->installOptions['user_email'];
    }

    public function getUserFirstName(): ?string
    {
        return $this->installOptions['user_firstname'];
    }

    public function getUserLastName(): ?string
    {
        return $this->installOptions['user_lastname'];
    }

    public function getUserPassword(): ?string
    {
        return $this->installOptions['user_password'];
    }

    public function isSampleDataRequired(): bool
    {
        return $this->installOptions['sample_data'];
    }

    public function getOrganizationName(): ?string
    {
        return $this->installOptions['organization_name'];
    }

    public function getApplicationUrl(): ?string
    {
        return $this->installOptions['application_url'];
    }

    public function getApplicationLanguage(): ?string
    {
        return $this->installOptions['language'];
    }

    public function getFormattingCode(): ?string
    {
        return $this->installOptions['formatting_code'];
    }

    public function getSkipTranslations(): bool
    {
        return $this->installOptions['skip_translations'];
    }

    public function getTimeout(): int
    {
        return $this->installOptions['timeout'];
    }
}
