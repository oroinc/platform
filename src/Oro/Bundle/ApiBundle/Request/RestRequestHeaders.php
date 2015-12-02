<?php

namespace Oro\Bundle\ApiBundle\Request;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\ChainProcessor\AbstractParameterBag;

class RestRequestHeaders extends AbstractParameterBag
{
    /** @var Request */
    protected $request;

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
    protected $parameters;

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
            return $this->request->headers->get($key);
        }

        $key = $this->normalizeKey($key);
        if (!isset($this->parameters[$key])) {
            return null;
        }
        $val = $this->parameters[$key];
        if (false === $val) {
            return null;
        }

        return empty($val)
            ? $this->request->headers->get($key)
            : $val['v'];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->ensureInternalStorageInitialized();

        $this->parameters[$this->normalizeKey($key)] = ['v' => $value];
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $this->ensureInternalStorageInitialized();

        $this->parameters[$this->normalizeKey($key)] = false;
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
                $result[$key] = $this->request->headers->get($key);
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

        $keys = array_keys($this->parameters);
        foreach ($keys as $key) {
            $this->parameters[$key] = false;
        }
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
    protected function normalizeKey($key)
    {
        return str_replace('_', '-', strtolower($key));
    }

    /**
     * Makes sure $this->parameters was initialized
     */
    protected function ensureInternalStorageInitialized()
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
