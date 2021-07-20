<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\ORM\Query;
use Psr\Container\ContainerInterface;

/**
 * Resolve query hints to a registered walkers.
 */
class QueryHintResolver implements QueryHintResolverInterface
{
    /** @var array [hint => ['class' => walker class, 'output' => bool, 'hint_provider' => hint provider id], ...] */
    private $walkers;

    /** @var ContainerInterface */
    private $walkerHintProviders;

    /** @var array [hint alias => hint, ...] */
    private $aliases;

    public function __construct(array $walkers, ContainerInterface $walkerHintProviders, array $aliases)
    {
        $this->walkers = $walkers;
        $this->walkerHintProviders = $walkerHintProviders;
        $this->aliases = $aliases;
    }

    /**
     * Resolves query hints
     */
    public function resolveHints(Query $query, array $hints = [])
    {
        if (!empty($hints)) {
            $this->addHints($query, $hints);
        }
        foreach ($query->getHints() as $hintName => $hintVal) {
            if (false !== $hintVal && isset($this->walkers[$hintName])) {
                $walker = $this->walkers[$hintName];
                $added = true;
                if (\is_string($walker['class'])) {
                    $added = $this->addHint(
                        $query,
                        $this->getHintName($walker),
                        $walker['class']
                    );
                }
                if ($added && isset($walker['hint_provider'])) {
                    /** @var QueryWalkerHintProviderInterface $walkerHintProvider */
                    $walkerHintProvider = $this->walkerHintProviders->get($walker['hint_provider']);
                    foreach ($walkerHintProvider->getHints($hintVal) as $walkerHint => $walkerHintVal) {
                        $this->addHint($query, $walkerHint, $walkerHintVal);
                    }
                }
            }
        }
    }

    /**
     * Adds a hint to a query object
     *
     * @param Query $query
     * @param string $name
     * @param mixed $value
     *
     * @return bool TRUE if the hint is added; otherwise, FALSE
     */
    public function addHint(Query $query, $name, $value)
    {
        if (Query::HINT_CUSTOM_TREE_WALKERS === $name) {
            return QueryUtil::addTreeWalker($query, $value);
        }

        $result = false;
        if (Query::HINT_CUSTOM_OUTPUT_WALKER !== $name) {
            $query->setHint($name, $value);
            $result = true;
        } elseif ($query->getHint($name) !== $value) {
            $query->setHint($name, $value);
            $result = true;
        }

        return $result;
    }

    /**
     * Adds hints to a query object
     */
    public function addHints(Query $query, array $hints)
    {
        foreach ($hints as $hint) {
            if (\is_array($hint)) {
                $this->addHint(
                    $query,
                    $this->resolveHintName($hint['name']),
                    isset($hint['value']) ? $this->resolveHintValue($query, $hint['value']) : true
                );
            } elseif (\is_string($hint)) {
                $this->addHint($query, $this->resolveHintName($hint), true);
            }
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function resolveHintName($name)
    {
        if (isset($this->aliases[$name])) {
            return $this->aliases[$name];
        }
        if (\defined("Doctrine\\ORM\\Query::$name")) {
            return \constant("Doctrine\\ORM\\Query::$name");
        }

        return $name;
    }

    /**
     * @param Query $query
     * @param mixed $value
     *
     * @return mixed
     */
    private function resolveHintValue(Query $query, $value)
    {
        if (\is_string($value) && $value[0] === ':') {
            $parameterName = substr($value, 1);
            if ($query->getParameter($parameterName) !== null) {
                return $query->getParameter($parameterName)->getValue();
            }
        }

        if (\is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->resolveHintValue($query, $item);
            }
        }

        return $value;
    }

    private function getHintName(array $walker): string
    {
        return $walker['output'] ? Query::HINT_CUSTOM_OUTPUT_WALKER : Query::HINT_CUSTOM_TREE_WALKERS;
    }
}
