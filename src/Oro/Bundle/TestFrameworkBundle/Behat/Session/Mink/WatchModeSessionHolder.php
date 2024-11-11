<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Session\Mink;

/**
 * Holds a behat mink session service which used with --watch mode.
 */
class WatchModeSessionHolder
{
    private array $sessions = [];
    private array $sessionAliases = [];
    private array $additionalOptions = [];
    private ?int $watchFromLine = null;
    private ?string $defaultSessionName = null;
    private bool $isWatchMode = false;
    private bool $isActualized = false;
    private ?int $lastProcessedStep = null;

    public function __construct(private readonly string $tmpDirPath)
    {
    }

    public function register(string $name, string $session): void
    {
        if ($this->hasSession($name)) {
            return;
        }
        $this->sessions[strtolower($name)] = $session;
        $this->updateState();
    }

    public function setSessionAlias(string $alias, string $sessionName): void
    {
        $this->actualizeState();
        if (!isset($this->sessionAliases[$alias])) {
            $this->sessionAliases[$alias] = $sessionName;
            $this->updateState();
        }
    }

    public function setLastProcessedStep(int $stepLine): void
    {
        $this->actualizeState();
        if ($this->lastProcessedStep < $stepLine) {
            $this->lastProcessedStep = $stepLine;
            $this->updateState();
        }
    }

    public function getLastProcessedStep(): ?int
    {
        $this->actualizeState();

        return $this->lastProcessedStep;
    }

    public function hasSessionAlias(string $alias): bool
    {
        $this->actualizeState();

        return isset($this->sessionAliases[$alias]);
    }

    public function getSessionNameByAlias(string $alias): ?string
    {
        if (!$this->hasSessionAlias($alias)) {
            return null;
        }

        return $this->sessionAliases[$alias];
    }

    public function setIsWatch(bool $value): void
    {
        $this->isWatchMode = $value;
    }

    public function setAdditionalOptions(array $options): void
    {
        $this->additionalOptions = $options;
    }

    public function getAdditionalOptions(): array
    {
        return $this->additionalOptions;
    }

    public function setWatchFrom(int $line): void
    {
        $this->watchFromLine = $line;
    }

    public function isWatchFrom(): bool
    {
        return null !== $this->watchFromLine;
    }

    public function getWatchFrom(): ?int
    {
        return $this->watchFromLine;
    }

    public function isWatchMode(): bool
    {
        return $this->isWatchMode;
    }

    public function hasSession(string $name): bool
    {
        $this->actualizeState();

        return isset($this->sessions[strtolower($name)]);
    }

    public function setDefaultSessionName(string $name): void
    {
        $this->actualizeState();
        if ($this->defaultSessionName !== strtolower($name)) {
            $this->defaultSessionName = strtolower($name);
            $this->updateState();
        }
    }

    public function getDefaultSessionName(): ?string
    {
        $this->actualizeState();

        return $this->defaultSessionName;
    }

    public function hasDefaultSession(): bool
    {
        $this->actualizeState();

        return null !== $this->defaultSessionName && isset($this->sessions[$this->defaultSessionName]);
    }

    public function getDefaultSession(): ?string
    {
        if (!$this->hasDefaultSession()) {
            return null;
        }

        return $this->sessions[$this->defaultSessionName];
    }

    protected function updateState(): void
    {
        $filePath = $this->getTmpSessionFilePath();
        file_put_contents(
            $filePath,
            json_encode(
                [
                    'sessions' => $this->sessions,
                    'default_session' => $this->defaultSessionName,
                    'session_aliases' => $this->sessionAliases,
                    'last_processed_step' => $this->lastProcessedStep,
                ]
            )
        );
    }

    public function actualizeState(bool $force = false): void
    {
        if (!$force && ($this->isActualized === true || !$this->isWatchFrom())) {
            return;
        }
        $filePath = $this->getTmpSessionFilePath();
        if (!file_exists($filePath)) {
            return;
        }
        $sessionData = file_get_contents($filePath);
        $data = json_decode($sessionData, true);
        if (isset($data['sessions'])) {
            $this->sessions = $data['sessions'];
        }
        if (isset($data['default_session'])) {
            $this->defaultSessionName = $data['default_session'];
        }
        if (isset($data['session_aliases'])) {
            $this->sessionAliases = $data['session_aliases'];
        }
        if (isset($data['last_processed_step'])) {
            $this->lastProcessedStep = $data['last_processed_step'];
        }
        $this->isActualized = true;
    }

    protected function getTmpSessionFilePath(): string
    {
        return $this->tmpDirPath . DIRECTORY_SEPARATOR . 'behat_watch_mode_sessions.json';
    }
}
