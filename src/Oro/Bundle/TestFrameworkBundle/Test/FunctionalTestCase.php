<?php

namespace Oro\Bundle\TestFrameworkBundle\Test;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

abstract class FunctionalTestCase extends WebTestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    protected function tearDown()
    {
        unset($this->container);

        parent::tearDown();
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        if (!$this->container) {
            $this->container = static::createClient()->getKernel()->getContainer();
        }

        return $this->container;
    }

    /**
     * @param array $classNames
     */
    protected function loadFixtures(array $classNames)
    {
        $fixtures = array();
        foreach ($classNames as $className) {
            $fixtures[] = new $className();
        }

        $executor = new ORMExecutor($this->getContainer()->get('doctrine.orm.entity_manager'));
        $executor->execute($fixtures, true);
    }
}
