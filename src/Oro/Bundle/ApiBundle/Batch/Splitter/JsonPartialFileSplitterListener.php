<?php

namespace Oro\Bundle\ApiBundle\Batch\Splitter;

/**
 * The JsonStreamingParser listener that constructs an in-memory representation of the JSON document
 * and that can be used by splitters that provide a possibility to limit the splitting time.
 */
class JsonPartialFileSplitterListener extends JsonFileSplitterListener
{
    /**
     * Gets the state of the listener.
     */
    public function getState(): array
    {
        return [
            'level'       => $this->level,
            'objectLevel' => $this->objectLevel,
            'objectKeys'  => \array_slice($this->objectKeys, 0, $this->level, true),
            'stack'       => $this->stack,
        ];
    }

    /**
     * Restores the state of the listener.
     */
    public function setState(array $data): void
    {
        if (\array_key_exists('level', $data)) {
            $this->level = $data['level'];
        }
        if (\array_key_exists('objectLevel', $data)) {
            $this->objectLevel = $data['objectLevel'];
        }
        if (\array_key_exists('objectKeys', $data)) {
            $this->objectKeys = $data['objectKeys'];
        }
        if (\array_key_exists('stack', $data)) {
            $this->stack = $data['stack'];
        }
    }
}
