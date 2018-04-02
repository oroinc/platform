<?php

namespace Oro\Bundle\MigrationBundle\Migration\Sorter;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\Exception\CircularReferenceException;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

/**
 * Basically code of this class comes from origin \Doctrine\Common\DataFixtures\Loader.
 * Issue solved is notices during fixtures sorting
 *
 * @TODO could be removed when https://github.com/doctrine/data-fixtures/issues/148 will be resolved
 */
class DataFixturesSorter
{
    /** @var array */
    protected $orderedFixtures = [];

    /** @var array */
    protected $fixtures = [];

    /**
     * Returns the array of data fixtures to execute.
     *
     * @param array $fixtures
     *
     * @return array $fixtures
     */
    public function sort(array $fixtures)
    {
        $this->fixtures        = $fixtures;
        $this->orderedFixtures = [];

        $usePrioritySorting     = $this->usePrioritySorting($fixtures);
        $useDependenciesSorting = $this->useDependenciesSorting($fixtures);


        if ($usePrioritySorting) {
            $this->orderFixturesByNumber();
        }

        if ($useDependenciesSorting) {
            $this->orderFixturesByDependencies($usePrioritySorting);
        }

        if (!($usePrioritySorting || $useDependenciesSorting)) {
            $this->orderedFixtures = $fixtures;
        }

        return $this->orderedFixtures;
    }

    /**
     * Order fixtures by priority
     *
     * @return array
     */
    protected function orderFixturesByNumber()
    {
        $this->orderedFixtures = $this->fixtures;
        usort(
            $this->orderedFixtures,
            function ($a, $b) {
                if ($a instanceof OrderedFixtureInterface && $b instanceof OrderedFixtureInterface) {
                    if ($a->getOrder() === $b->getOrder()) {
                        return 0;
                    }

                    return $a->getOrder() < $b->getOrder() ? -1 : 1;
                } elseif ($a instanceof OrderedFixtureInterface) {
                    return $a->getOrder() === 0 ? 0 : 1;
                } elseif ($b instanceof OrderedFixtureInterface) {
                    return $b->getOrder() === 0 ? 0 : -1;
                }

                return 0;
            }
        );
    }

    /**
     * @param bool $usedPrioritySorting
     *
     * @return array
     * @throws CircularReferenceException
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function orderFixturesByDependencies($usedPrioritySorting)
    {
        $sequenceForClasses = $orderedFixtures = [];

        // If fixtures were already ordered by number then we need
        // to remove classes which are not instances of OrderedFixtureInterface
        // in case fixtures implementing DependentFixtureInterface exist.
        // This is because, in that case, the method orderFixturesByDependencies
        // will handle all fixtures which are not instances of
        // OrderedFixtureInterface
        if ($usedPrioritySorting) {
            $this->orderedFixtures = array_filter(
                $this->orderedFixtures,
                function ($fixture) {
                    return $fixture instanceof OrderedFixtureInterface;
                }
            );
        }

        // First we determine which classes has dependencies and which don't
        foreach ($this->fixtures as $fixture) {
            $fixtureClass = get_class($fixture);

            if ($fixture instanceof OrderedFixtureInterface) {
                continue;
            } elseif ($fixture instanceof DependentFixtureInterface) {
                $dependenciesClasses = $fixture->getDependencies();

                $this->validateDependencies($fixtureClass, $dependenciesClasses);

                // We mark this class as unsequenced
                $sequenceForClasses[$fixtureClass] = -1;
            } else {
                // This class has no dependencies, so we assign 0
                $sequenceForClasses[$fixtureClass] = 0;
            }
        }

        // Now we order fixtures by sequence
        $sequence  = 1;
        $lastCount = -1;

        while (($count = count($unsequencedClasses = $this->getUnsequencedClasses($sequenceForClasses))) > 0
            && $count !== $lastCount) {
            foreach ($unsequencedClasses as $key => $class) {
                $fixture                 = $this->fixtures[$class];
                $dependencies            = $fixture->getDependencies();
                $unsequencedDependencies = $this->getUnsequencedClasses($sequenceForClasses, $dependencies);

                if (count($unsequencedDependencies) === 0) {
                    $sequenceForClasses[$class] = $sequence++;
                }
            }

            $lastCount = $count;
        }

        // If there're fixtures unsequenced left and they couldn't be sequenced,
        // it means we have a circular reference
        if ($count > 0) {
            $msg = 'Classes "%s" have produced a CircularReferenceException. ';
            $msg .= 'An example of this problem would be the following: Class C has class B as its dependency. ';
            $msg .= 'Then, class B has class A has its dependency. Finally, class A has class C as its dependency. ';
            $msg .= 'This case would produce a CircularReferenceException.';

            throw new CircularReferenceException(sprintf($msg, implode(',', $unsequencedClasses)));
        } else {
            // We order the classes by sequence
            asort($sequenceForClasses);

            foreach ($sequenceForClasses as $class => $sequence) {
                // If fixtures were ordered
                $orderedFixtures[] = $this->fixtures[$class];
            }
        }

        $this->orderedFixtures = array_merge($this->orderedFixtures, $orderedFixtures);
    }

    /**
     * @param string $fixtureClass
     * @param mixed  $dependenciesClasses
     *
     * @return bool
     */
    protected function validateDependencies($fixtureClass, $dependenciesClasses)
    {
        if (!is_array($dependenciesClasses) || empty($dependenciesClasses)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Method "%s" in class "%s" must return an array of classes which are'
                    . ' dependencies for the fixture, and it must be NOT empty.',
                    'getDependencies',
                    $fixtureClass
                )
            );
        }

        if (in_array($fixtureClass, $dependenciesClasses)) {
            throw new \InvalidArgumentException(
                sprintf('Class "%s" can\'t have itself as a dependency', $fixtureClass)
            );
        }

        $loadedFixtureClasses = array_keys($this->fixtures);
        foreach ($dependenciesClasses as $class) {
            if (!in_array($class, $loadedFixtureClasses) && class_exists($class)) {
                throw new \RuntimeException(
                    sprintf(
                        'Fixture "%s" was declared as a dependency, but it should be added in fixture loader first.',
                        $class
                    )
                );
            }
        }

        return true;
    }

    /**
     * @param array      $sequences
     * @param null|array $classes
     *
     * @return array
     */
    protected function getUnsequencedClasses(array $sequences, array $classes = null)
    {
        $unsequencedClasses = array();

        if (is_null($classes)) {
            $classes = array_keys($sequences);
        }

        foreach ($classes as $class) {
            // might not be set if depends on ordered fixture
            if (isset($sequences[$class]) && $sequences[$class] === -1) {
                $unsequencedClasses[] = $class;
            }
        }

        return $unsequencedClasses;
    }

    /**
     * @param array $fixtures
     *
     * @return bool
     */
    protected function usePrioritySorting($fixtures)
    {
        foreach ($fixtures as $fixture) {
            if ($fixture instanceof OrderedFixtureInterface) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $fixtures
     *
     * @return bool
     */
    protected function useDependenciesSorting($fixtures)
    {
        foreach ($fixtures as $fixture) {
            if ($fixture instanceof DependentFixtureInterface) {
                return true;
            }
        }

        return false;
    }
}
