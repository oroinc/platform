<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Resolver;

use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

/**
 * Returns value casted to string.
 */
class AliceToStringResolver implements ResolverInterface, ReferencesAwareInterface
{
    /**
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * @var string
     */
    protected static $regex = '/^<toString\((?P<value>[^<]*)\)>$/';

    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function setReferences(Collection $references): void
    {
        if ($this->resolver instanceof ReferencesAwareInterface) {
            $this->resolver->setReferences($references);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($value)
    {
        if (\is_string($value) && \preg_match(self::$regex, $value, $matches)) {
            $value = $matches['value'] ?: null;
        }

        $value = $this->doResolve($value);

        if (null !== $value && isset($matches['value'])) {
            $value = $this->convertValueToString($value);
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function doResolve($value)
    {
        if (!\is_string($value) || !\preg_match('/^<\((.+)\)>$/', $value, $matches)) {
            return $this->resolver->resolve($value);
        }

        $args = preg_replace_callback(
            '{(".*?[^\\\\]")|((?<!\\\\)@([a-z0-9_\-.]+[a-z0-9]){1}(->[a-z0-9_-]+)?)}i',
            function ($match) {
                return $match[1] ?: var_export($this->resolver->resolve($match[2]), true);
            },
            $matches[1]
        );

        return eval('return ' . $args . ';');
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function convertValueToString($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        return (string)$value;
    }
}
