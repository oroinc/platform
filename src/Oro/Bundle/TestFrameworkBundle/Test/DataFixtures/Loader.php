<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\Exception\CircularReferenceException;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

/**
 * This class responsible for loading a different kind of data fixtures.
 *
 * This class is based on {@see Doctrine\Common\DataFixtures\Loader}
 * created by Jonathan H. Wage <jonwage@gmail.com>.
 */
class Loader
{
    /** @var FixtureFactoryInterface */
    protected $factory;

    /** @var FixtureIdentifierResolverInterface */
    protected $identifierResolver;

    /** @var array [fixture id => fixture, ...] */
    protected $fixtures = [];

    /** @var bool */
    private $orderFixturesByNumber = false;

    /** @var bool */
    private $orderFixturesByDependencies = false;

    /**
     * @param FixtureFactoryInterface            $factory
     * @param FixtureIdentifierResolverInterface $identifierResolver
     */
    public function __construct(
        FixtureFactoryInterface $factory,
        FixtureIdentifierResolverInterface $identifierResolver
    ) {
        $this->factory = $factory;
        $this->identifierResolver = $identifierResolver;
    }

    /**
     * Adds a fixture object instance to the loader.
     *
     * @param mixed $fixture The fixture object or a fixture identifier, e.g. class name or file name
     *
     * @return FixtureInterface|null The fixture object or NULL if the fixture already exists in the loader
     *
     * @throws \InvalidArgumentException if the given fixture is not supported
     */
    public function addFixture($fixture)
    {
        $this->assertFixture($fixture);
        $fixtureId = $this->identifierResolver->resolveId($fixture);
        $this->fixtures[$fixtureId] = $fixture;

        if ($fixture instanceof OrderedFixtureInterface) {
            $this->orderFixturesByNumber = true;
        } elseif ($fixture instanceof DependentFixtureInterface) {
            $dependencies = $fixture->getDependencies();
            $this->assertDependencies($dependencies, $fixture);
            if (!empty($dependencies)) {
                $dependencies = array_map(
                    function ($dependency) {
                        return $this->identifierResolver->resolveId($dependency);
                    },
                    $dependencies
                );
                if (in_array($fixtureId, $dependencies, true)) {
                    throw new \InvalidArgumentException(
                        sprintf('The fixture "%s" can\'t have itself as a dependency.', $fixtureId)
                    );
                }

                $this->orderFixturesByDependencies = true;
                foreach ($dependencies as $dependency) {
                    $this->addFixture($dependency);
                }
            }
        }

        return $fixture;
    }

    /**
     * Returns the array of data fixtures to execute.
     *
     * @return FixtureInterface[]
     */
    public function getFixtures()
    {
        $orderedFixtures = [];
        if ($this->orderFixturesByNumber) {
            $orderedFixtures = $this->orderFixturesByNumber();
        }
        if ($this->orderFixturesByDependencies) {
            if (!empty($orderedFixtures)) {
                // If fixtures were already ordered by number then we need
                // to remove fixtures which are not instances of OrderedFixtureInterface
                // in case fixtures implementing DependentFixtureInterface exist.
                // This is because, in that case, the method orderFixturesByDependencies
                // will handle all fixtures which are not instances of
                // OrderedFixtureInterface
                $count = count($orderedFixtures);
                for ($i = 0; $i < $count; ++$i) {
                    if (!($orderedFixtures[$i] instanceof OrderedFixtureInterface)) {
                        unset($orderedFixtures[$i]);
                    }
                }
            }
            $orderedFixtures = array_merge($orderedFixtures, $this->orderFixturesByDependencies());
        }
        if (empty($orderedFixtures)) {
            $orderedFixtures = array_values($this->fixtures);
        }

        return $orderedFixtures;
    }

    /**
     * @return FixtureInterface[]
     */
    private function orderFixturesByNumber()
    {
        $orderedFixtures = $this->fixtures;
        usort($orderedFixtures, function ($a, $b) {
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
        });

        return $orderedFixtures;
    }

    /**
     * @return FixtureInterface[]
     */
    private function orderFixturesByDependencies()
    {
        // First we determine which fixtures have dependencies and which don't
        $sequences = $this->getSequences();

        // Now we order fixtures by sequence
        $sequence = 1;
        $lastCount = -1;
        $unsequencedFixtures = $this->getUnsequencedFixtures($sequences);
        $count = count($unsequencedFixtures);
        while ($count > 0 && $count !== $lastCount) {
            foreach ($unsequencedFixtures as $fixtureId) {
                $fixture = $this->fixtures[$fixtureId];
                if (0 === count($this->getUnsequencedFixtures($sequences, $fixture->getDependencies()))) {
                    $sequences[$fixtureId] = $sequence++;
                    break;
                }
            }

            $lastCount = $count;
            $unsequencedFixtures = $this->getUnsequencedFixtures($sequences);
            $count = count($unsequencedFixtures);
        }

        $orderedFixtures = [];

        // If there're fixtures unsequenced left and they couldn't be sequenced,
        // it means we have a circular reference
        if ($count > 0) {
            throw new CircularReferenceException(
                sprintf(
                    'Fixtures "%s" have produced a CircularReferenceException.'
                    . ' An example of this problem would be the following: Fixture C has fixture B as its dependency.'
                    . ' Then, fixture B has fixture A has its dependency.'
                    . ' Finally, fixture A has fixture C as its dependency.'
                    . ' This case would produce a CircularReferenceException.',
                    implode(',', $unsequencedFixtures)
                )
            );
        } else {
            asort($sequences);
            foreach ($sequences as $fixtureId => $sequence) {
                $orderedFixtures[] = $this->fixtures[$fixtureId];
            }
        }

        return $orderedFixtures;
    }

    /**
     * @return array [fixture id => sequence, ...]
     */
    private function getSequences()
    {
        $sequences = [];
        foreach ($this->fixtures as $fixtureId => $fixture) {
            if ($fixture instanceof OrderedFixtureInterface) {
                continue;
            }
            if ($fixture instanceof DependentFixtureInterface && count($fixture->getDependencies()) > 0) {
                // We mark this fixture as unsequenced
                $sequences[$fixtureId] = -1;
            } else {
                // This fixture has no dependencies, so we assign 0
                $sequences[$fixtureId] = 0;
            }
        }

        return $sequences;
    }

    /**
     * @param array         $sequences    [fixture id => sequence, ...]
     * @param string[]|null $dependencies [fixture id, ...]
     *
     * @return array
     */
    protected function getUnsequencedFixtures(array $sequences, $dependencies = null)
    {
        $unsequencedFixtures = [];
        if (null === $dependencies) {
            $dependencies = array_keys($sequences);
        }
        foreach ($dependencies as $dependency) {
            if (-1 === $sequences[$dependency]) {
                $unsequencedFixtures[] = $dependency;
            }
        }

        return $unsequencedFixtures;
    }

    /**
     * @param object $fixture
     */
    private function assertFixture($fixture)
    {
        if (!$fixture instanceof FixtureInterface) {
            throw new \InvalidArgumentException(
                sprintf('The class "%s" must implement "%s".', get_class($fixture), FixtureInterface::class)
            );
        }
        if ($fixture instanceof OrderedFixtureInterface && $fixture instanceof DependentFixtureInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The class "%s" can\'t implement "%s" and "%s" at the same time.',
                    get_class($fixture),
                    OrderedFixtureInterface::class,
                    DependentFixtureInterface::class
                )
            );
        }
    }

    /**
     * @param mixed  $dependencies
     * @param object $fixture
     */
    private function assertDependencies($dependencies, $fixture)
    {
        if (!is_array($dependencies)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The method "getDependencies" in class "%s" must return an array of fixture identifiers'
                    . ' which are dependencies for the fixture.',
                    get_class($fixture)
                )
            );
        }
    }
}
