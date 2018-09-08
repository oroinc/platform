<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Data fixtures loader that allow to use nelmio/alice to load data fixtures in functional tests.
 */
class DataFixturesLoader extends Loader
{
    /** @var ContainerInterface */
    private $container;

    /** @var AliceFixtureLoader */
    private $aliceFixtureLoader;

    /**
     * @param FixtureFactoryInterface            $factory
     * @param FixtureIdentifierResolverInterface $identifierResolver
     * @param ContainerInterface                 $container
     */
    public function __construct(
        FixtureFactoryInterface $factory,
        FixtureIdentifierResolverInterface $identifierResolver,
        ContainerInterface $container
    ) {
        parent::__construct($factory, $identifierResolver);
        $this->container = $container;
        $this->aliceFixtureLoader = $container->get('oro_test.alice_fixture_loader');
    }

    /**
     * {@inheritdoc}
     */
    public function addFixture($fixture)
    {
        $fixtureId = $this->identifierResolver->resolveId($fixture);
        if (isset($this->fixtures[$fixtureId])) {
            return null;
        }

        if (!is_object($fixture)) {
            $fixture = $this->factory->createFixture($fixtureId);
        }

        if (null !== $fixture) {
            if ($fixture instanceof AliceFixtureLoaderAwareInterface) {
                $fixture->setLoader($this->aliceFixtureLoader);
            }
            if ($fixture instanceof ContainerAwareInterface) {
                $fixture->setContainer($this->container);
            }
        }
        $fixture = parent::addFixture($fixture);

        return $fixture;
    }

    /**
     * Converts fixture dependencies to be allow to set short path to alice file fixture as dependency.
     * {@inheritdoc}
     */
    protected function getUnsequencedFixtures(array $sequences, $dependencies = null)
    {
        $fixtureIdentifierResolver = new AliceFixtureIdentifierResolver($this->container->get('kernel'));

        if (is_array($dependencies)) {
            foreach ($dependencies as $id => $dependency) {
                $dependencies[$id] = $fixtureIdentifierResolver->resolveId($dependency);
            }
        }

        return parent::getUnsequencedFixtures($sequences, $dependencies);
    }
}
