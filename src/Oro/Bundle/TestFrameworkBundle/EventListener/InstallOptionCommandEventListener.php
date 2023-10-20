<?php

namespace Oro\Bundle\TestFrameworkBundle\EventListener;

use Oro\Bundle\TestFrameworkBundle\Provider\InstallDefaultOptionsProvider;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
        $output = $event->getOutput();
        $io = new SymfonyStyle($input, $output);

        $this->updateOption($input, $io, 'user-name', $this->defaultOptionsProvider->getUserName());
        $this->updateOption(
            $input,
            $io,
            'user-email',
            $this->defaultOptionsProvider->getUserEmail()
        );
        $this->updateOption(
            $input,
            $io,
            'user-firstname',
            $this->defaultOptionsProvider->getUserFirstName()
        );
        $this->updateOption(
            $input,
            $io,
            'user-lastname',
            $this->defaultOptionsProvider->getUserLastName()
        );
        $this->updateOption(
            $input,
            $io,
            'user-password',
            $this->defaultOptionsProvider->getUserPassword()
        );
        $this->updateOption(
            $input,
            $io,
            'sample-data',
            $this->defaultOptionsProvider->isSampleDataRequired() ? 'y' : 'n'
        );
        $this->updateOption(
            $input,
            $io,
            'organization-name',
            $this->defaultOptionsProvider->getOrganizationName()
        );
        $this->updateOption(
            $input,
            $io,
            'application-url',
            $this->defaultOptionsProvider->getApplicationUrl()
        );
        $this->updateOption(
            $input,
            $io,
            'language',
            $this->defaultOptionsProvider->getApplicationLanguage()
        );
        $this->updateOption(
            $input,
            $io,
            'formatting-code',
            $this->defaultOptionsProvider->getFormattingCode()
        );
        $this->updateOption(
            $input,
            $io,
            'skip-translations',
            $this->defaultOptionsProvider->getSkipTranslations()
        );
        $this->updateOption($input, $io, 'timeout', $this->defaultOptionsProvider->getTimeout());
        $input->setInteractive(false);
    }

    public function updateOption(
        InputInterface $input,
        SymfonyStyle $io,
        string $option,
        $defaultOption
    ): void {
        if ($input->getOption($option) && $input->getOption($option) !== $defaultOption) {
            $io->error([
                \sprintf(
                    'Passing install command options is not supported for the test environment '.
                    'because functional tests rely on exact values. '.
                    'To change them, correct the `oro_test_framework.install_options` configuration.'
                ),
            ]);
        }
        $input->setOption($option, $defaultOption);
    }
}
