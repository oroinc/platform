<?php

namespace Oro\Bundle\TestFrameworkBundle\EventListener;

use Oro\Bundle\TestFrameworkBundle\Provider\InstallDefaultOptionsProvider;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Setting parameters in the test environment during installation
 */
class InstallOptionCommandEventListener
{
    public function __construct(
        private InstallDefaultOptionsProvider $defaultOptionsProvider
    ) {
    }

    public function onInitialize(ConsoleEvent $event)
    {
        $input = $event->getInput();

        $this->updateOption($input, 'user-name', $this->defaultOptionsProvider->getUserName());
        $this->updateOption(
            $input,
            'user-email',
            $this->defaultOptionsProvider->getUserEmail()
        );
        $this->updateOption(
            $input,
            'user-firstname',
            $this->defaultOptionsProvider->getUserFirstName()
        );
        $this->updateOption(
            $input,
            'user-lastname',
            $this->defaultOptionsProvider->getUserLastName()
        );
        $this->updateOption(
            $input,
            'user-password',
            $this->defaultOptionsProvider->getUserPassword()
        );
        $this->updateOption(
            $input,
            'sample-data',
            $this->defaultOptionsProvider->isSampleDataRequired() ? 'y' : 'n'
        );
        $this->updateOption(
            $input,
            'organization-name',
            $this->defaultOptionsProvider->getOrganizationName()
        );
        $this->updateOption(
            $input,
            'application-url',
            $this->defaultOptionsProvider->getApplicationUrl()
        );
        $this->updateOption(
            $input,
            'language',
            $this->defaultOptionsProvider->getApplicationLanguage()
        );
        $this->updateOption(
            $input,
            'formatting-code',
            $this->defaultOptionsProvider->getFormattingCode()
        );
        $this->updateOption(
            $input,
            'skip-translations',
            $this->defaultOptionsProvider->getSkipTranslations()
        );
        $input->setOption('timeout', $this->defaultOptionsProvider->getTimeout());
        $input->setInteractive(false);
    }

    public function updateOption(
        InputInterface $input,
        string $option,
        $defaultOption
    ): void {
        if ($input->getOption($option) && $input->getOption($option) !== $defaultOption) {
            throw new \LogicException(
                'Passing install command options is not supported for the test environment ' .
                'because functional tests rely on exact values. ' .
                'To change them, correct the `oro_test_framework.install_options` configuration.'
            );
        }
        $input->setOption($option, $defaultOption);
    }
}
