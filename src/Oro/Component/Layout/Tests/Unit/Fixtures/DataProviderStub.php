<?php

namespace Oro\Component\Layout\Tests\Unit\Fixtures;

class DataProviderStub
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->data);
    }

    public function is(string $name): bool
    {
        return \array_key_exists($name, $this->data);
    }

    public function get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function set(string $name, $value): void
    {
        $this->data[$name] = $value;
    }

    public function hasValue(string $name): bool
    {
        return $this->has($name);
    }

    public function isValue(string $name): bool
    {
        return $this->is($name);
    }

    public function getValue(string $name)
    {
        return $this->get($name);
    }

    public function setValue(string $name, $value): void
    {
        $this->set($name, $value);
    }

    public function getCount(): int
    {
        return count($this->data);
    }
}
