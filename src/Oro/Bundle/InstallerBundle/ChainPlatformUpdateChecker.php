<?php

namespace Oro\Bundle\InstallerBundle;

/**
 * Delegates the checking if an application can be updated to a new version to child checkers
 * and returns all result messages from all checkers.
 */
class ChainPlatformUpdateChecker implements PlatformUpdateCheckerInterface
{
    /** @var iterable|PlatformUpdateCheckerInterface[] */
    private $checkers;

    /**
     * @param iterable|PlatformUpdateCheckerInterface[] $checkers
     */
    public function __construct(iterable $checkers)
    {
        $this->checkers = $checkers;
    }

    /**
     * {@inheritDoc}
     */
    public function checkReadyToUpdate(): ?array
    {
        $messages = [];
        foreach ($this->checkers as $checker) {
            $checkerMessages = $checker->checkReadyToUpdate();
            if ($checkerMessages) {
                $messages[] = $checkerMessages;
            }
        }

        if (!$messages) {
            return null;
        }
        if (count($messages) === 1) {
            return reset($messages);
        }

        return array_merge(...$messages);
    }
}
