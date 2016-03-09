<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\ActionBundle\Exception\CircularReferenceException;

class ArraySubstitution
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

    /**
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
    }

    /**
     * @param array $things
     */
    public function apply(array &$things)
    {
        $bounded = $this->replace($things);

        if ($this->clearUnboundedSubstitutions) {
            $this->clearUnbounded($things, $bounded);
        }
    }

    /**
     * @param array $things
     * @return array list of replacements that was participated in this substitution
     * @throws CircularReferenceException
     */
    protected function replace(array &$things)
    {
        $scopeMap = $this->getScopedMap($things);

        $keysIndex = array_keys($things);
        $valuesIndex = array_values($things);
        $bounded = [];

        foreach ($scopeMap as $target => $replacement) {
            if (in_array($target, $this->map, true)) { //ignore replacement targets
                continue;
            }

            $bounded[] = $replacement = $this->lookUpReplacements($scopeMap, $replacement, $this->maxDepth);

            $replacementPos = array_search($replacement, $keysIndex, true);
            unset($keysIndex[$replacementPos]);
            $targetPos = array_search($target, $keysIndex, true);
            $valuesIndex[$targetPos] = $valuesIndex[$replacementPos];
            unset($valuesIndex[$replacementPos]);
        }

        $things = array_combine($keysIndex, $valuesIndex);

        return $bounded;
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
     * @param array $map
     * @param string $key
     * @param int $maxDepth
     * @param int $depth step counter for function self usage purpose
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
            Substitution\CircularReferenceSearch::assert($map);
        }
        $this->map = $map;

        return $this;
    }
}
