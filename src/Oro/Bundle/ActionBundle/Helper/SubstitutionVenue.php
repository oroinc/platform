<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\ActionBundle\Exception\CircularReferenceException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class SubstitutionVenue implements LoggerAwareInterface
{
    const SUBSTITUTION_PATH_MAX_DEPTH = 10;

    /* @var array */
    private $map;

    /* @var bool */
    private $ignoreCircularReferences;

    /* @var bool */
    private $clearUnboundedSubstitutions;

    /* @var int */
    private $maxDepth;

    /** @var LoggerInterface */
    private $logger;

    /** @var callable */
    private $substitutionLogging;

    /**
     * SubstitutionVenue constructor.
     * @param bool $clearUnboundedSubstitutions result of substitution will be cleared form unused replacers
     * @param int $substitutionMapMaxDepth
     * @param bool $ignoreCircularReferences if set to true and circular ref met in map will throw an exception
     */
    public function __construct(
        $clearUnboundedSubstitutions = true,
        $substitutionMapMaxDepth = self::SUBSTITUTION_PATH_MAX_DEPTH,
        $ignoreCircularReferences = false
    ) {
        $this->clearUnboundedSubstitutions = $clearUnboundedSubstitutions;
        $this->maxDepth = $substitutionMapMaxDepth;
        $this->ignoreCircularReferences = $ignoreCircularReferences;
        $this->logger = new NullLogger();
        $this->substitutionLogging = function ($target, $replacement, $targetName, $replacementName) {
            $this->logger->debug(
                sprintf('Action substitution. "%s" substituted by "%s"', $targetName, $replacementName)
            );
        };
    }

    /**
     * Create result array with substituted values if found. Does not apply changes to argument.
     * @param array $things associative array of named elements that can be matched in map and replaced if condition met
     * @param callable $modifier
     * @return array
     */
    public function substitute(array $things, callable $modifier = null)
    {
        $this->apply($things, $modifier);

        return $things;
    }

    /**
     * @param array $things
     * @param callable $modifier function that will be invoked during concrete substitution
     */
    public function apply(array &$things, callable $modifier = null)
    {

        $bounded = $this->replace($things, $modifier);

        if ($this->clearUnboundedSubstitutions) {
            $this->clearUnbounded($things, $bounded);
        }
    }

    /**
     * @param array $things
     * @param callable $modifier
     * @return array list of replacements that was participated in this substitution
     * @throws CircularReferenceException
     */
    protected function replace(array &$things, callable $modifier)
    {
        $scopeMap = $this->getScopedMap($things);

        $keysIndex = array_keys($things);
        $valuesIndex = array_values($things);
        $bounded = [];

        foreach ($scopeMap as $target => $replacement) {
            if ($this->isReplacement($target)) {
                continue;
            }

            $bounded[] = $replacement = $this->lookUpReplacements($scopeMap, $replacement, $this->maxDepth);

            $replacementPos = array_search($replacement, $keysIndex, true);
            unset($keysIndex[$replacementPos]);
            $targetPos = array_search($target, $keysIndex, true);
            $keysIndex[$targetPos] = $replacement;
            //outer modifications callback
            call_user_func(
                $this->wrapModifier($modifier),
                $valuesIndex[$targetPos],
                $valuesIndex[$replacementPos],
                $target,
                $replacement
            );
            $valuesIndex[$targetPos] = $valuesIndex[$replacementPos];
            unset($valuesIndex[$replacementPos]);
        }

        $things = array_combine($keysIndex, $valuesIndex);

        return $bounded;
    }

    /**
     * @param callable|null $modifier
     * @return callable|\Closure
     */
    private function wrapModifier(callable $modifier = null)
    {
        if ($modifier) {
            return function () use ($modifier) {
                $args = func_get_args();
                call_user_func_array($this->substitutionLogging, $args);
                call_user_func_array($modifier, $args);
            };
        } else {
            return $this->substitutionLogging;
        }
    }


    /**
     * @param array $scope
     * @return array
     */
    private function getScopedMap(array &$scope)
    {
        return $this->filterValueKey($this->map, function ($value, $key) use (&$scope) {
            return array_key_exists($value, $scope) && array_key_exists($key, $scope);
        });
    }

    /**
     * @param array $array
     * @param callable $filter
     * @return array
     */
    private function filterValueKey(array $array, callable $filter)
    {
        return iterator_to_array(new \CallbackFilterIterator(new \ArrayIterator($array), $filter));
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function isReplacement($name)
    {
        return in_array($name, $this->map, true);
    }

    /**
     * @param array $map
     * @param $key
     * @param int $maxDepth
     * @param int $depth
     * @return string the key of last found point
     * @throws CircularReferenceException
     */
    private function lookUpReplacements(array &$map, $key, $maxDepth, $depth = 1)
    {
        if (!array_key_exists($key, $map)) {
            return $key;
        }

        if ($maxDepth > 0 && $depth >= $maxDepth) {
            throw new CircularReferenceException(
                sprintf('Max depth (%d) of reference reached on "%s" key', $maxDepth, $key)
            );
        }

        return $this->lookUpReplacements($map, $map[$key], $maxDepth, ++$depth);

    }

    /**
     * @param array $things
     * @param array $bounded
     */
    private function clearUnbounded(array &$things, array $bounded)
    {
        $things = $this->filterValueKey($things, function ($value, $key) use (&$bounded) {
            return false === (!in_array($key, $bounded, true) && in_array($key, $this->map, true));
        });
    }

    /**
     * @param array $map
     * @return $this
     */
    public function setMap(array $map)
    {
        if (!$this->ignoreCircularReferences) {
            self::assertNoCircularReferences($map);
        }
        $this->map = $map;

        return $this;
    }

    /**
     * @param $pairs
     * @throws CircularReferenceException
     */
    public static function assertNoCircularReferences($pairs)
    {
        $walker = function (array &$list, $target, $point) use (&$walker) {
            if (null === $point) {
                return false;
            }

            if (array_key_exists($point, $list)) {
                if ($list[$point] === $target || $list[$point] === $point) {
                    return true;
                } else {
                    return $walker($list, $target, $list[$point]);
                }
            }

            return false;
        };

        foreach ($pairs as $target => $replacement) {
            if ($walker($pairs, $target, $replacement)) {
                throw new CircularReferenceException(
                    sprintf(
                        'Circular reference detected. On replacement %s that points tp %s target.',
                        $target,
                        $replacement
                    )
                );
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return null|void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
}
