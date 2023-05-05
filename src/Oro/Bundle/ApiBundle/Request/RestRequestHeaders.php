<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Component\ChainProcessor\AbstractParameterBag;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;

/**
 * A collection of headers and their values for REST API requests.
 */
class RestRequestHeaders extends AbstractParameterBag
{
    private Request $request;
    /**
     * each value of the array can be:
     *  []             - a parameter exists, but a value should be retrieved using $this->getHeaderValue()
     *  false          - a parameter was removed
     *  ['v' => value] - a parameter exists and its value is stored in this array
     */
    private ?array $parameters = null;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        if (null === $this->parameters) {
            return $this->hasHeader($key);
        }

        $key = $this->normalizeKey($key);

        return isset($this->parameters[$key]) && false !== $this->parameters[$key];
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): mixed
    {
        if (null === $this->parameters) {
            return $this->getHeaderValue($key);
        }

        $key = $this->normalizeKey($key);
        if (!isset($this->parameters[$key])) {
            return null;
        }
        $val = $this->parameters[$key];
        if (false === $val) {
            return null;
        }

        return !$val
            ? $this->getHeaderValue($key)
            : $val['v'];
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->ensureInternalStorageInitialized();

        $this->parameters[$this->normalizeKey($key)] = ['v' => $value];
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key): void
    {
        $this->ensureInternalStorageInitialized();

        $this->parameters[$this->normalizeKey($key)] = false;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $result = [];
        if (null === $this->parameters) {
            $names = $this->getHeaderNames();
            foreach ($names as $name) {
                $result[$name] = $this->getHeaderValue($name);
            }
        } else {
            foreach ($this->parameters as $key => $parameter) {
                if (false !== $parameter) {
                    $result[$key] = $this->get($key);
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->ensureInternalStorageInitialized();

        $keys = array_keys($this->parameters);
        foreach ($keys as $key) {
            $this->parameters[$key] = false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        if (null === $this->parameters) {
            return \count($this->getHeaderNames());
        }

        $result = 0;
        foreach ($this->parameters as $parameter) {
            if (false !== $parameter) {
                $result++;
            }
        }

        return $result;
    }

    private function normalizeKey(string $key): string
    {
        return str_replace('_', '-', strtolower($key));
    }

    /**
     * Makes sure $this->parameters was initialized
     */
    private function ensureInternalStorageInitialized(): void
    {
        if (null === $this->parameters) {
            $this->parameters = [];

            $names = $this->getHeaderNames();
            foreach ($names as $name) {
                $this->parameters[$this->normalizeKey($name)] = [];
            }
        }
    }

    /**
     * @return string[]
     */
    private function getHeaderNames(): array
    {
        return $this->request->headers->keys();
    }

    private function hasHeader(string $name): bool
    {
        return $this->request->headers->has($name);
    }

    private function getHeaderValue(string $name): array|string|null
    {
        $lowerName = strtolower($name);
        if ('accept' === $lowerName) {
            return $this->getAcceptHeaderValue($name);
        }
        if ('accept-language' === $lowerName) {
            return $this->request->getLanguages();
        }
        if ('accept-charset' === $lowerName) {
            return $this->request->getCharsets();
        }
        if ('accept-encoding' === $lowerName) {
            return $this->request->getEncodings();
        }

        return $this->request->headers->get($name);
    }

    /**
     * @param string $name
     *
     * @return string[]
     */
    private function getAcceptHeaderValue(string $name): array
    {
        $result = [];
        $items = AcceptHeader::fromString($this->request->headers->get($name))->all();
        foreach ($items as $item) {
            $value = $item->getValue();
            $attributes = $item->getAttributes();
            if ($attributes) {
                $value .= '; ' . HeaderUtils::toString($attributes, ';');
            }
            $result[] = $value;
        }

        return $result;
    }
}
