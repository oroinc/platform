<?php

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\ORM\Query;

class QueryHintResolver implements QueryHintResolverInterface
{
    /** @var array */
    protected $walkers = [];

    /** @var string */
    protected $aliases = [];

    /**
     * Maps a query hint to a tree walker
     *
     * @param string                                $hint               The name of the query hint
     * @param string                                $walkerClass        The FQCN of the walker
     * @param QueryWalkerHintProviderInterface|null $walkerHintProvider The provider of the walker parameters
     * @param string|null                           $alias              The alias of the query hint
     */
    public function addTreeWalker(
        $hint,
        $walkerClass,
        QueryWalkerHintProviderInterface $walkerHintProvider = null,
        $alias = null
    ) {
        $this->walkers[$hint] = [
            'class'         => $walkerClass,
            'output'        => false,
            'hint_provider' => $walkerHintProvider
        ];
        if ($alias) {
            $this->aliases[$alias] = $hint;
        }
    }

    /**
     * Maps a query hint to an output walker
     *
     * @param string                                $hint               The name of the query hint
     * @param string                                $walkerClass        The FQCN of the walker
     * @param QueryWalkerHintProviderInterface|null $walkerHintProvider The provider of the walker parameters
     * @param string|null                           $alias              The alias of the query hint
     */
    public function addOutputWalker(
        $hint,
        $walkerClass,
        QueryWalkerHintProviderInterface $walkerHintProvider = null,
        $alias = null
    ) {
        $this->walkers[$hint] = [
            'class'         => $walkerClass,
            'output'        => true,
            'hint_provider' => $walkerHintProvider
        ];
        if ($alias) {
            $this->aliases[$alias] = $hint;
        }
    }

    /**
     * Resolves query hints
     *
     * @param Query $query
     * @param array $hints
     */
    public function resolveHints(Query $query, array $hints = [])
    {
        if (!empty($hints)) {
            $this->addHints($query, $hints);
        }
        foreach ($query->getHints() as $hintName => $hintVal) {
            if (false !== $hintVal && isset($this->walkers[$hintName])) {
                $walker = $this->walkers[$hintName];
                $added  = $this->addHint(
                    $query,
                    $walker['output'] ? Query::HINT_CUSTOM_OUTPUT_WALKER : Query::HINT_CUSTOM_TREE_WALKERS,
                    $walker['class']
                );
                if ($added && isset($walker['hint_provider'])) {
                    /** @var QueryWalkerHintProviderInterface $walkerHintProvider */
                    $walkerHintProvider = $walker['hint_provider'];
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
     * @param Query  $query
     * @param string $name
     * @param mixed  $value
     *
     * @return bool TRUE if the hint is added; otherwise, FALSE
     */
    public function addHint(Query $query, $name, $value)
    {
        $result = false;
        if ($name === Query::HINT_CUSTOM_TREE_WALKERS) {
            $walkers = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);
            if (false === $walkers) {
                $walkers = [$value];
                $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $walkers);
                $result = true;
            } elseif (!in_array($value, $walkers, true)) {
                $walkers[] = $value;
                $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $walkers);
                $result = true;
            }
        } elseif ($name === Query::HINT_CUSTOM_OUTPUT_WALKER) {
            if ($query->getHint($name) !== $value) {
                $query->setHint($name, $value);
                $result = true;
            }
        } else {
            $query->setHint($name, $value);
            $result = true;
        }

        return $result;
    }

    /**
     * Adds hints to a query object
     *
     * @param Query $query
     * @param array $hints
     */
    public function addHints(Query $query, array $hints)
    {
        foreach ($hints as $hint) {
            if (is_array($hint)) {
                $this->addHint(
                    $query,
                    $this->resolveHintName($hint['name']),
                    isset($hint['value']) ? $hint['value'] : true
                );
            } elseif (is_string($hint)) {
                $this->addHint($query, $this->resolveHintName($hint), true);
            }
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function resolveHintName($name)
    {
        if (isset($this->aliases[$name])) {
            return $this->aliases[$name];
        }
        if (defined("Doctrine\\ORM\\Query::$name")) {
            return constant("Doctrine\\ORM\\Query::$name");
        }

        return $name;
    }
}
