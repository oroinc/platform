<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\IsolatorInterface;
use Predis\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RedisCacheIsolator implements IsolatorInterface
{
    private const REDIS_ENABLED_ENV_VAR = 'CACHE';

    /** @var ContainerInterface */
    private $container;

    /** @var string[] */
    private $knownClients;

    /** @var RedisCacheManipulator[] */
    private $manipulators = [];

    /**
     * @param ContainerInterface $container
     * @param string[] $knownClients
     */
    public function __construct(ContainerInterface $container, array $knownClients)
    {
        $this->container = $container;
        $this->knownClients = $knownClients;
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return \getenv(self::REDIS_ENABLED_ENV_VAR) === 'REDIS' && (bool)$this->knownClients;
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return 'Redis';
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Save Redis state</info>');
        $event->writeln($this->buildMessage($this->saveRedisState(), __FUNCTION__));
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $event->writeln('<info>Restore Redis state</info>');
        $event->writeln($this->buildMessage($this->restoreRedisState(), __FUNCTION__));
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function restoreState(RestoreStateEvent $event)
    {
        $event->writeln('<info>Restore Redis state</info>');
        $event->writeln($this->buildMessage($this->restoreRedisState(), __FUNCTION__));
    }

    /** {@inheritdoc} */
    public function isOutdatedState()
    {
        foreach ($this->getManipulators() as $manipulator) {
            $data = $manipulator->restoreData();
            if ($data) {
                return true;
            }
        }

        return false;
    }

    /** {@inheritdoc} */
    public function getTag()
    {
        return 'cache';
    }

    private function saveRedisState(): array
    {
        $startTime = \microtime(true);

        $results = [];
        foreach ($this->getManipulators() as $manipulator) {
            $results[$manipulator->getName()] = $manipulator->saveRedisState();
        }

        return [\microtime(true) - $startTime, $results];
    }

    private function restoreRedisState(): array
    {
        $startTime = \microtime(true);

        $results = [];
        foreach ($this->getManipulators() as $manipulator) {
            $results[$manipulator->getName()] = $manipulator->restoreRedisState();
        }

        return [\microtime(true) - $startTime, $results];
    }

    private function buildMessage(array $data, string $method): string
    {
        return \sprintf(
            'Duration: %d ms. %s. Function: %s()',
            $data[0] * 1000,
            \implode(
                ' and ',
                \array_map(
                    function ($name, $count) {
                        return \sprintf('%d %s keys', $count, $name);
                    },
                    \array_keys($data[1]),
                    \array_values($data[1])
                )
            ),
            $method
        );
    }

    /**
     * @return RedisCacheManipulator[]
     */
    private function getManipulators(): array
    {
        if (!$this->manipulators) {
            if (!$this->container->has('oro.redis_config.client_locator')) {
                throw new \RuntimeException(
                    \sprintf(
                        'Incorrect container configuration: service "%s" required',
                        'oro.redis_config.client_locator'
                    )
                );
            }

            $clientLocator = $this->container->get('oro.redis_config.client_locator');
            foreach ($this->knownClients as $serviceName => $type) {
                if (!$clientLocator->has($serviceName)) {
                    throw new \RuntimeException(
                        \sprintf(
                            'Required redis client "%s" for cache type "%s" not registered',
                            $serviceName,
                            $type
                        )
                    );
                }

                $service = $clientLocator->get($serviceName);
                if (!$service instanceof Client) {
                    throw new \RuntimeException(
                        \sprintf(
                            'Required redis client "%s" for cache type "%s" must be instance of %s class',
                            $serviceName,
                            $type,
                            Client::class
                        )
                    );
                }

                $this->manipulators[] = new RedisCacheManipulator($service, $type);
            }

            if (!$this->manipulators) {
                throw new \RuntimeException(
                    \sprintf('Incorrect container configuration: no registered radis clients')
                );
            }
        }

        return $this->manipulators;
    }
}
