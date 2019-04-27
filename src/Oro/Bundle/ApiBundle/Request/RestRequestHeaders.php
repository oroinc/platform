<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Component\ChainProcessor\AbstractParameterBag;
use Oro\Component\ChainProcessor\ParameterValueResolverInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A collection of headers and their values for REST API requests.
 */
class RestRequestHeaders extends AbstractParameterBag
{
    /** @var Request */
    private $request;

    /**
     * @var array|null
     *  [
     *      normalized_key => [] | false | ['v' => value],
     *      ...
     *  ]
     * value is:
     *  []             - a parameter exists, but a value should be retrieved using $this->request->headers->get($key)
     *  false          - a parameter was removed
     *  ['v' => value] - a parameter exists and its value is stored in this array
     */
    private $parameters;

    /** @var ParameterValueResolverInterface[] [key => ParameterValueResolverInterface, ...] */
    private $resolvers = [];

    /** @var array [key => value, ...] */
    private $resolvedItems = [];

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        if (null === $this->parameters) {
            return $this->request->headers->has($key);
        }

        $key = $this->normalizeKey($key);

        return isset($this->parameters[$key]) && false !== $this->parameters[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (null === $this->parameters) {
            if (!$this->request->headers->has($key)) {
                return null;
            }
            if (\array_key_exists($key, $this->resolvedItems)) {
                return $this->resolvedItems[$key];
            }

            $value = $this->request->headers->get($key);
        } else {
            $key = $this->normalizeKey($key);
            if (!isset($this->parameters[$key])) {
                return null;
            }
            $val = $this->parameters[$key];
            if (false === $val) {
                return null;
            }
            if (\array_key_exists($key, $this->resolvedItems)) {
                return $this->resolvedItems[$key];
            }

            $value = empty($val)
                ? $this->request->headers->get($key)
                : $val['v'];
        }

        if (isset($this->resolvers[$key])) {
            $resolver = $this->resolvers[$key];
            if ($resolver->supports($value)) {
                $value = $resolver->resolve($value);
            }
            $this->resolvedItems[$key] = $value;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->ensureInternalStorageInitialized();

        $key = $this->normalizeKey($key);
        $this->parameters[$key] = ['v' => $value];
        unset($this->resolvedItems[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $this->ensureInternalStorageInitialized();

        $key = $this->normalizeKey($key);
        $this->parameters[$key] = false;
        unset($this->resolvedItems[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function setResolver($key, ?ParameterValueResolverInterface $resolver)
    {
        $key = $this->normalizeKey($key);
        if (null === $resolver) {
            unset($this->resolvers[$key]);
        } else {
            $this->resolvers[$key] = $resolver;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $result = [];
        if (null === $this->parameters) {
            $keys = $this->request->headers->keys();
            foreach ($keys as $key) {
                $result[$key] = $this->get($key);
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
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->ensureInternalStorageInitialized();

        $keys = \array_keys($this->parameters);
        foreach ($keys as $key) {
            $this->parameters[$key] = false;
        }
        $this->resolvedItems = [];
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if (null === $this->parameters) {
            return $this->request->headers->count();
        }

        $result = 0;
        foreach ($this->parameters as $parameter) {
            if (false !== $parameter) {
                $result++;
            }
        }

        return $result;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function normalizeKey($key)
    {
        return \str_replace('_', '-', \strtolower($key));
    }

    /**
     * Makes sure $this->parameters was initialized
     */
    private function ensureInternalStorageInitialized()
    {
        if (null === $this->parameters) {
            $this->parameters = [];

            $keys = $this->request->headers->keys();
            foreach ($keys as $key) {
                $this->parameters[$this->normalizeKey($key)] = [];
            }
        }
    }
}
