<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Command;

use Oro\Bundle\InstallerBundle\Command\Provider\InputOptionProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Intl\Locales;

/**
 * Utility methods for commands that accept localization options (--language, --formatting-code).
 */
trait LocalizationOptionsCommandTrait
{
    private InputOptionProvider $inputOptionProvider;

    private function addLocalizationOptions(): self
    {
        /** @var Command $this */
        $this
            ->addOption('language', null, InputOption::VALUE_OPTIONAL, 'Localization language code')
            ->addOption('formatting-code', null, InputOption::VALUE_OPTIONAL, 'Localization formatting code')
        ;

        return $this;
    }

    private function getLocalizationOptionsHelp(): string
    {
        return <<<'HELP'

The <info>--language</info> and <info>--formatting</info> code options can be used to specify
the localization language and the localization formatting setting that are used
during execution of this command:

  <info>php %command.full_name% --language=<language-code> --formatting-code=<formatting-code></info>
  <info>php %command.full_name% --language=en --formatting-code=en_US</info>
HELP;
    }

    private function addLocalizationOptionsUsage() : self
    {
        return $this
            ->addUsage('--language=en --formatting-code=en_US')
            ->addUsage('--language=en_US --formatting-code=en_US')
            ->addUsage('--language=de_DE --formatting-code=de_DE')
            ->addUsage('--language=fr_FR --formatting-code=fr_FR')
        ;
    }

    private function getFormattingCodeFromOptions(string $defaultFormattingCode): string
    {
        return $this->inputOptionProvider->get(
            'formatting-code',
            'Formatting Code',
            $defaultFormattingCode,
            [
                'constructorArgs' => [$defaultFormattingCode],
                'settings' => [
                    'validator' => [
                        function ($value) {
                            $this->validateFormattingCode($value);
                            return $value;
                        }
                    ]
                ]
            ]
        );
    }

    private function getLanguageFromOptions(string $defaultLanguage): string
    {
        return $this->inputOptionProvider->get(
            'language',
            'Language',
            $defaultLanguage,
            [
                'constructorArgs' => [$defaultLanguage],
                'settings' => [
                    'validator' => [
                        function ($value) {
                            $this->validateLanguage($value);
                            return $value;
                        }
                    ]
                ]
            ]
        );
    }

    private function getLocalizationParametersFromOptions(
        string $defaultFormattingCode,
        string $defaultLanguage
    ): array {
        return [
            '--formatting-code' => $this->getFormattingCodeFromOptions($defaultFormattingCode),
            '--language' => $this->getLanguageFromOptions($defaultLanguage)
        ];
    }

    private function validateLocalizationOptions(InputInterface $input): void
    {
        $formattingCode = $input->getOption('formatting-code');
        if ($formattingCode) {
            $this->validateFormattingCode($formattingCode);
        }

        $language = (string)$input->getOption('language');
        if ($language) {
            $this->validateLanguage($language);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function validateFormattingCode(string $locale): void
    {
        $allowedLocales = array_keys(Locales::getNames());
        if (!in_array($locale, $allowedLocales, true)) {
            throw $this->getLocalizationValueException('formatting', $locale, $allowedLocales);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function validateLanguage(string $language): void
    {
        $allowedLocales = array_keys(Locales::getNames());
        if (!in_array($language, $allowedLocales, true)) {
            throw $this->getLocalizationValueException('language', $language, $allowedLocales);
        }
    }

    private function getLocalizationValueException(
        string $optionName,
        string $locale,
        array $allowedLocales
    ): \InvalidArgumentException {
        $exceptionMessage = sprintf('"%s" is not a valid %s code!', $locale, $optionName);
        $alternatives = $this->getLocaleCodeAlternatives($locale, $allowedLocales);
        if ($alternatives) {
            $exceptionMessage .= sprintf("\nDid you mean %s?\n", $alternatives);
        }

        return new \InvalidArgumentException($exceptionMessage);
    }

    private function getLocaleCodeAlternatives(string $name, array $items): string
    {
        $alternatives = [];
        foreach ($items as $item) {
            $lev = levenshtein($name, $item);
            if ($lev <= \strlen($name) / 2 || str_contains($item, $name)) {
                $alternatives[$item] = $lev;
            }
        }
        asort($alternatives);

        return implode(', ', array_keys($alternatives));
    }
}
