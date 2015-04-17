<?php

namespace Oro\Bundle\UIBundle\ConfigExpression;

use Symfony\Component\Templating\Helper\CoreAssetsHelper;

use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\Func\AbstractFunction;

class Asset extends AbstractFunction
{
    /** @var CoreAssetsHelper */
    protected $assetsHelper;

    /** @var mixed */
    protected $path;

    /** @var mixed */
    protected $packageName;

    /**
     * @param CoreAssetsHelper $assetsHelper
     */
    public function __construct(CoreAssetsHelper $assetsHelper)
    {
        $this->assetsHelper = $assetsHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'asset';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $params = [$this->path];
        if ($this->packageName !== null) {
            $params[] = $this->packageName;
        }

        return $this->convertToArray($params);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        $params = [$this->path];
        if ($this->packageName !== null) {
            $params[] = $this->packageName;
        }

        return $this->convertToPhpCode($params, $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $count = count($options);
        if ($count >= 1 && $count <= 2) {
            $this->path = reset($options);
            if (!$this->path) {
                throw new InvalidArgumentException('Path must not be empty.');
            }
            if ($count > 1) {
                $this->packageName = next($options);
            }
        } else {
            throw new InvalidArgumentException(
                sprintf('Options must have 1 or 2 elements, but %d given.', count($options))
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageParameters($context)
    {
        return [
            '{{ path }}'        => $this->resolveValue($context, $this->path),
            '{{ packageName }}' => $this->resolveValue($context, $this->packageName)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doEvaluate($context)
    {
        $path = $this->resolveValue($context, $this->path);
        if ($path === null) {
            return $path;
        }
        if (!is_string($path)) {
            $this->addError(
                $context,
                sprintf(
                    'Expected a string value for the path, got "%s".',
                    is_object($path) ? get_class($path) : gettype($path)
                )
            );

            return $path;
        }

        $packageName = $this->resolveValue($context, $this->packageName);
        if ($packageName !== null && !is_string($packageName)) {
            $this->addError(
                $context,
                sprintf(
                    'Expected null or a string value for the package name, got "%s".',
                    is_object($packageName) ? get_class($packageName) : gettype($packageName)
                )
            );

            return $path;
        }

        return $this->assetsHelper->getUrl($this->normalizeAssetsPath($path), $packageName);
    }

    /**
     * Normalizes assets path
     * E.g. '@AcmeTestBundle/Resources/public/images/picture.png' => 'bundles/acmetest/images/picture.png'
     *
     * @param string $path
     *
     * @return string
     */
    private function normalizeAssetsPath($path)
    {
        if ('@' == $path[0]) {
            $path = ltrim($path, '@');
            $path = preg_replace('@bundle/resources/public@', '', strtolower($path));
            $path = 'bundles/'. $path;
        }

        return $path;
    }
}
