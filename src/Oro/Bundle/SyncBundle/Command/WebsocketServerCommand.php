<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\Command;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\ServerLauncherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The WebsocketServerCommand from GeniusesOfSymfony/WebSocketBundle 3.x uses a profile shortcut that is absent in
 * global Symfony options. This generates an exception when the custom option is not equal to the global one.
 *
 * This file is a copy of the 3.x version of
 * {@see \Gos\Bundle\WebSocketBundle\Command\WebsocketServerCommand}
 *
 * Copyright (c) 2015 Johann Saunier <johann_27@hotmail.fr>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */

#[AsCommand(name: 'gos:websocket:server', description: 'Starts the websocket server')]
final class WebsocketServerCommand extends Command
{
    /**
     * @var string|null
     */
    protected static $defaultName = 'gos:websocket:server';

    private ServerLauncherInterface $serverLauncher;

    private string $host;

    private int $port;

    private ?ServerRegistry $serverRegistry;

    public function __construct(
        ServerLauncherInterface $entryPoint,
        string $host,
        int $port,
        ?ServerRegistry $serverRegistry = null
    ) {
        parent::__construct();

        if (null === $serverRegistry) {
            trigger_deprecation(
                'gos/web-socket-bundle',
                '3.12',
                'Not passing the "%s" to the "%s" constructor is deprecated and will be required as of 4.0.',
                ServerRegistry::class,
                self::class
            );
        }

        $this->serverLauncher = $entryPoint;
        $this->port = $port;
        $this->host = $host;
        $this->serverRegistry = $serverRegistry;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Starts the websocket server')
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                'Name of the server to start, launches the first registered server if not specified'
            )
            ->addOption('profile', '', InputOption::VALUE_NONE, 'Enable profiling of the server')
            ->addOption('host', 'a', InputOption::VALUE_OPTIONAL, 'The hostname of the websocket server')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'The port of the websocket server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $name */
        $name = $input->getArgument('name');

        /** @var string $host */
        $host = null === $input->getOption('host') ? $this->host : $input->getOption('host');

        /** @var int $port */
        $port = null === $input->getOption('port') ? $this->port : $input->getOption('port');

        if (!is_numeric($port)) {
            throw new InvalidArgumentException('The port option must be a numeric value.');
        }

        /** @var bool $profile */
        $profile = $input->getOption('profile');

        $this->serverLauncher->launch($name, $host, (int) $port, $profile);

        return 0;
    }

    public function complete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('name') && null !== $this->serverRegistry) {
            $suggestions->suggestValues(array_keys($this->serverRegistry->getServers()));

            return;
        }
    }
}
