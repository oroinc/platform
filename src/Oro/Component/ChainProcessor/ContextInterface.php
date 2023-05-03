<?php

namespace Oro\Component\ChainProcessor;

/**
 * Represents an execution context for processors.
 */
interface ContextInterface extends \ArrayAccess
{
    /**
     * Checks whether an attribute exists in the context.
     */
    public function has(string $key): bool;

    /**
     * Gets a value of an attribute from the context.
     * When an attribute does not exist the returned value is NULL.
     */
    public function get(string $key): mixed;

    /**
     * Adds or updates a value of an attribute in the context.
     */
    public function set(string $key, mixed $value): void;

    /**
     * Removes an attribute from the context.
     */
    public function remove(string $key): void;

    /**
     * Gets an identifier of processing action.
     */
    public function getAction(): string;

    /**
     * Sets an identifier of processing action.
     */
    public function setAction(string $action): void;

    /**
     * Gets a group starting from which processors should be executed.
     */
    public function getFirstGroup(): ?string;

    /**
     * Sets a group starting from which processors should be executed.
     */
    public function setFirstGroup(?string $group): void;

    /**
     * Gets a group after which processors should not be executed.
     */
    public function getLastGroup(): ?string;

    /**
     * Sets a group after which processors should not be executed.
     */
    public function setLastGroup(?string $group): void;

    /**
     * Checks whether there is at least one group to be skipped.
     */
    public function hasSkippedGroups(): bool;

    /**
     * Gets all groups to be skipped.
     *
     * @return string[]
     */
    public function getSkippedGroups(): array;

    /**
     * Clears a list of groups to be skipped.
     */
    public function resetSkippedGroups(): void;

    /**
     * Adds the given group to a list of groups to be skipped.
     */
    public function skipGroup(string $group): void;

    /**
     * Removes the given group to a list of groups to be skipped.
     */
    public function undoGroupSkipping(string $group): void;

    /**
     * Checks whether result data exists.
     */
    public function hasResult(): bool;

    /**
     * Gets result data.
     */
    public function getResult(): mixed;

    /**
     * Sets result data.
     */
    public function setResult(mixed $data): void;

    /**
     * Removes result data.
     */
    public function removeResult(): void;
}
