<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Command;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Decorates base Behat Command handles exceptions and changes the return code on error
 *
 * we can't use the console event
 * @link https://symfony.com/doc/current/components/console/events.html#the-consoleevents-terminate-event Terminate
 * because the Behat command doesn't use a dispatcher
 */
class CommandDecorator extends BaseCommand
{
    private const BEHAT_ERROR_EXIT_CODE = 7;

    public function __construct(private Command $command)
    {
    }

    public static function getDefaultName(): ?string
    {
        return Command::getDefaultName();
    }

    public static function getDefaultDescription(): ?string
    {
        return Command::getDefaultDescription();
    }

    public function ignoreValidationErrors()
    {
        $this->command->ignoreValidationErrors();
    }

    public function setApplication(?Application $application = null)
    {
        $this->command->setApplication($application);
    }

    public function setHelperSet(HelperSet $helperSet)
    {
        $this->command->setHelperSet($helperSet);
    }

    public function getHelperSet(): ?HelperSet
    {
        return $this->command->getHelperSet();
    }

    public function getApplication(): ?Application
    {
        return $this->command->getApplication();
    }

    public function isEnabled()
    {
        return $this->command->isEnabled();
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        try {
            return $this->command->run($input, $output);
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage(), self::BEHAT_ERROR_EXIT_CODE, $e);
        }
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        $this->command->complete($input, $suggestions);
    }

    public function setCode(callable $code): static
    {
        return $this->command->setCode($code);
    }

    public function mergeApplicationDefinition(bool $mergeArgs = true): void
    {
        $this->command->mergeApplicationDefinition($mergeArgs);
    }

    public function setDefinition(InputDefinition|array $definition): static
    {
        return $this->command->setDefinition($definition);
    }

    public function getDefinition(): InputDefinition
    {
        return $this->command->getDefinition();
    }

    public function getNativeDefinition(): InputDefinition
    {
        return $this->command->getNativeDefinition();
    }

    public function addArgument(
        string $name,
        ?int $mode = null,
        string $description = '',
        mixed $default = null
    ): static {
        return $this->command->addArgument($name, $mode, $description, $default);
    }

    public function addOption(
        string $name,
        array|string|null $shortcut = null,
        ?int $mode = null,
        string $description = '',
        mixed $default = null
    ): static {
        return $this->command->addOption($name, $shortcut, $mode, $description, $default);
    }

    public function setName(string $name): static
    {
        return $this->command->setName($name);
    }

    public function setProcessTitle(string $title): static
    {
        return $this->command->setProcessTitle($title);
    }

    public function getName(): ?string
    {
        return $this->command->getName();
    }

    public function setHidden(bool $hidden = true): static
    {
        return $this->command->setHidden($hidden);
    }

    public function isHidden(): bool
    {
        return $this->command->isHidden();
    }

    public function setDescription(string $description): static
    {
        return $this->command->setDescription($description);
    }

    public function getDescription(): string
    {
        return $this->command->getDescription();
    }

    public function setHelp(string $help): static
    {
        return $this->command->setHelp($help);
    }

    public function getHelp(): string
    {
        return $this->command->getHelp();
    }

    public function getProcessedHelp(): string
    {
        return $this->command->getProcessedHelp();
    }

    public function setAliases(iterable $aliases): static
    {
        return $this->command->setAliases($aliases);
    }

    public function getAliases(): array
    {
        return $this->command->getAliases();
    }

    public function getSynopsis(bool $short = false): string
    {
        return $this->command->getSynopsis($short);
    }

    public function addUsage(string $usage): static
    {
        return $this->command->addUsage($usage);
    }

    public function getUsages(): array
    {
        return $this->command->getUsages();
    }

    public function getHelper(string $name): mixed
    {
        return $this->command->getHelper($name);
    }
}
