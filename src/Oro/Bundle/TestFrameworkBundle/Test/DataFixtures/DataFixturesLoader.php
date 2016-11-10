<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
    }

    /**
     * {@inheritdoc}
     */
    public function addFixture($fixture)
    {
        $fixture = parent::addFixture($fixture);
        if (null !== $fixture) {
            if ($fixture instanceof AliceFixtureLoaderAwareInterface) {
                if (null === $this->aliceFixtureLoader) {
                    $this->aliceFixtureLoader = new AliceFixtureLoader();
                }
                $fixture->setLoader($this->aliceFixtureLoader);
            }
            if ($fixture instanceof ContainerAwareInterface) {
                $fixture->setContainer($this->container);
            }
        }

        return $fixture;
    }
}
