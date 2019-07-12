<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\DoctrineIsolator;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Checks if fixtures can be loaded.
 */
class FixturesChecker implements HealthCheckerInterface
{
    /**
     * @var FixtureLoader
     */
    protected $fixtureLoader;

    /**
     * @var DoctrineIsolator
     */
    protected $doctrineIsolator;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @param FixtureLoader $fixtureLoader
     * @param DoctrineIsolator $doctrineIsolator
     * @param KernelInterface $kernel
     */
    public function __construct(
        FixtureLoader $fixtureLoader,
        DoctrineIsolator $doctrineIsolator,
        KernelInterface $kernel
    ) {
        $this->fixtureLoader = $fixtureLoader;
        $this->doctrineIsolator = $doctrineIsolator;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeFeatureTested::BEFORE => 'checkFixtures'
        ];
    }

    /**
     * @param BeforeFeatureTested $event
     */
    public function checkFixtures(BeforeFeatureTested $event)
    {
        $this->kernel->boot();
        $this->doctrineIsolator->initReferences();
        $fixtureFiles = $this->doctrineIsolator->getFixtureFiles($event->getFeature()->getTags());
        foreach ($fixtureFiles as $fixtureFile) {
            try {
                $this->fixtureLoader->load($fixtureFile);
            } catch (\Exception $e) {
                $message = sprintf('Error while load "%s" fixture', $fixtureFile);
                $this->addDetails($message, $event, $e);
                $this->addError($message);
            } catch (\Throwable $e) {
                $message = sprintf('Error while load "%s" fixture', $fixtureFile);
                $this->addDetails($message, $event, $e);
                $this->addError($message);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isFailure()
    {
        return !empty($this->errors);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'fixtures';
    }

    /**
     * @param $message
     * @param BeforeFeatureTested $event
     * @param \Throwable|\Exception $exception
     */
    private function addDetails(&$message, BeforeFeatureTested $event, $exception)
    {
        $message = sprintf(
            $message.PHP_EOL.
            '   Suite: %s'.PHP_EOL.
            '   Feature: %s'.PHP_EOL.
            '   %s',
            $event->getSuite()->getName(),
            $event->getFeature()->getFile(),
            $exception->getMessage()
        );
    }

    /**
     * @param string $message
     */
    private function addError($message)
    {
        $this->errors[] = $message;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
