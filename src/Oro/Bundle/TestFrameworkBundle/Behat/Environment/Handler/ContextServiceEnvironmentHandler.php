<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Environment\Handler;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\ContextFactory;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Environment\Handler\EnvironmentHandler;
use Behat\Testwork\Suite\Exception\SuiteConfigurationException;
use Behat\Testwork\Suite\Suite;
use FriendsOfBehat\SymfonyExtension\Context\Environment\InitializedSymfonyExtensionEnvironment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds environment for each behat suite.
 * Gets contexts from the behat container and initializes them.
 */
final class ContextServiceEnvironmentHandler implements EnvironmentHandler
{
    private ContainerInterface $container;

    private ContextFactory $contextFactory;

    /** @var ContextInitializer[] */
    private array $contextInitializers = [];

    public function __construct(ContainerInterface $container, ContextFactory $contextFactory)
    {
        $this->container = $container;
        $this->contextFactory = $contextFactory;
    }

    public function registerContextInitializer(ContextInitializer $contextInitializer): void
    {
        $this->contextInitializers[] = $contextInitializer;
    }

    public function supportsSuite(Suite $suite): bool
    {
        return $suite->hasSetting('contexts');
    }

    public function buildEnvironment(Suite $suite): Environment
    {
        $this->container->reset();

        $initializedEnvironment = new InitializedSymfonyExtensionEnvironment($suite);
        foreach ($this->getSuiteContextsServices($suite) as $serviceId) {
            if ($this->container->has($serviceId)) {
                /** @var Context $context */
                $context = $this->container->get($serviceId);
                $this->initializeContext($context);
            } else {
                $context = $this->contextFactory->createContext($serviceId);
            }

            $initializedEnvironment->registerContext($context);
        }

        return $initializedEnvironment;
    }

    private function initializeContext(Context $context): void
    {
        foreach ($this->contextInitializers as $contextInitializer) {
            $contextInitializer->initializeContext($context);
        }
    }

    /**
     * @return string[]
     *
     * @throws SuiteConfigurationException If "contexts" setting is not an array
     */
    private function getSuiteContextsServices(Suite $suite): array
    {
        $contexts = $suite->getSetting('contexts');

        if (!is_array($contexts)) {
            throw new SuiteConfigurationException(
                sprintf(
                    '"contexts" setting of the "%s" suite is expected to be an array, %s given.',
                    $suite->getName(),
                    gettype($contexts)
                ),
                $suite->getName()
            );
        }

        return array_map([$this, 'normalizeContext'], $contexts);
    }

    private function normalizeContext(array|string $context): string
    {
        if (is_array($context)) {
            return (string)current(array_keys($context));
        }

        return $context;
    }

    public function supportsEnvironmentAndSubject(Environment $environment, $testSubject = null): bool
    {
        return $environment instanceof InitializedSymfonyExtensionEnvironment;
    }

    /**
     * We don't do anything because we have isolation for every feature instead of scenario
     */
    public function isolateEnvironment(Environment $environment, $testSubject = null): Environment
    {
        return $environment;
    }
}
