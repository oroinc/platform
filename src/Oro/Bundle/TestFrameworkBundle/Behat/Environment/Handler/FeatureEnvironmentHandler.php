<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Environment\Handler;

use Behat\Behat\Context\ContextClass\ClassResolver;
use Behat\Behat\Context\ContextFactory;
use Behat\Behat\Context\Environment\InitializedContextEnvironment;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Environment\Handler\EnvironmentHandler;
use Behat\Testwork\Suite\Exception\SuiteConfigurationException;
use Behat\Testwork\Suite\Suite;
use Symfony\Component\HttpKernel\KernelInterface;

class FeatureEnvironmentHandler implements EnvironmentHandler
{
    /**
     * @var ContextFactory
     */
    private $factory;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var ClassResolver[]
     */
    private $classResolvers = [];

    /**
     * Initializes handler.
     *
     * @param ContextFactory $factory
     */
    public function __construct(ContextFactory $factory, KernelInterface $kernel)
    {
        $this->factory = $factory;
        $this->kernel = $kernel;
    }

    /**
     * Registers context class resolver.
     *
     * @param ClassResolver $resolver
     */
    public function registerClassResolver(ClassResolver $resolver)
    {
        $this->classResolvers[] = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsSuite(Suite $suite)
    {
        return $suite->hasSetting('contexts');
    }

    /**
     * {@inheritdoc}
     */
    public function buildEnvironment(Suite $suite)
    {
        try {
            $this->kernel->boot();
            $environment = new InitializedContextEnvironment($suite);

            foreach ($this->getNormalizedContextSettings($suite) as $context) {
                $context = $this->factory->createContext($this->resolveClass($context[0]), $context[1]);
                $environment->registerContext($context);
            }

            $this->kernel->shutdown();
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf('Error while build "%s" suite envirorment', $suite->getName()),
                0,
                $e
            );
        }

        return $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEnvironmentAndSubject(Environment $environment, $testSubject = null)
    {
        return $environment instanceof InitializedContextEnvironment;
    }

    /**
     * We don't do anything because we have isolation for every feature instead of scenario
     */
    public function isolateEnvironment(Environment $environment, $testSubject = null)
    {
        return $environment;
    }

    /**
     * Returns normalized suite context settings.
     *
     * @param Suite $suite
     *
     * @return array
     */
    private function getNormalizedContextSettings(Suite $suite)
    {
        return array_map(
            function ($context) {
                $class = $context;
                $arguments = [];

                if (is_array($context)) {
                    $class = current(array_keys($context));
                    $arguments = $context[$class];
                }

                return [$class, $arguments];
            },
            $this->getSuiteContexts($suite)
        );
    }

    /**
     * Returns array of context classes configured for the provided suite.
     *
     * @param Suite $suite
     *
     * @return string[]
     *
     * @throws SuiteConfigurationException If `contexts` setting is not an array
     */
    private function getSuiteContexts(Suite $suite)
    {
        if (!is_array($suite->getSetting('contexts'))) {
            throw new SuiteConfigurationException(
                sprintf(
                    '`contexts` setting of the "%s" suite is expected to be an array, %s given.',
                    $suite->getName(),
                    gettype($suite->getSetting('contexts'))
                ),
                $suite->getName()
            );
        }

        return $suite->getSetting('contexts');
    }

    /**
     * Resolves class using registered class resolvers.
     *
     * @param string $class
     *
     * @return string
     */
    private function resolveClass($class)
    {
        foreach ($this->classResolvers as $resolver) {
            if ($resolver->supportsClass($class)) {
                return $resolver->resolveClass($class);
            }
        }

        return $class;
    }
}
