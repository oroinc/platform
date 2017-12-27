<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\OroYamlParser;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\DoctrineIsolator;
use Symfony\Component\HttpKernel\KernelInterface;

class FixturesChecker implements HealthCheckerInterface
{
    /**
     * @var FixtureLoader
     */
    protected $fixtureLoader;

    /**
     * @var OroYamlParser
     */
    protected $parser;

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
     * @param OroYamlParser $parser
     * @param DoctrineIsolator $doctrineIsolator
     * @param KernelInterface $kernel
     */
    public function __construct(
        FixtureLoader $fixtureLoader,
        OroYamlParser $parser,
        DoctrineIsolator $doctrineIsolator,
        KernelInterface $kernel
    ) {
        $this->kernel = $kernel;
        $this->fixtureLoader = $fixtureLoader;
        $this->parser = $parser;
        $this->doctrineIsolator = $doctrineIsolator;
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
                $file = $this->fixtureLoader->findFile($fixtureFile);
                $data = $this->parser->parse($file);
            } catch (\Exception $e) {
                $message = sprintf('Error while find and parse "%s" fixture', $fixtureFile);
                $this->addDetails($message, $event, $e);
                $this->addError($message);
                continue;
            }

            try {
                $this->fixtureLoader->load($data);
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
