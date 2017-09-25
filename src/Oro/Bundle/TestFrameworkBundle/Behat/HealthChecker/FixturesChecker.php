<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoader;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\OroYamlParser;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\DoctrineIsolator;

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
     * @var array
     */
    protected $errors = [];

    /**
     * @param FixtureLoader $fixtureLoader
     * @param OroYamlParser $parser
     * @param DoctrineIsolator $doctrineIsolator
     */
    public function __construct(FixtureLoader $fixtureLoader, OroYamlParser $parser, DoctrineIsolator $doctrineIsolator)
    {
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
        $fixtureFiles = $this->doctrineIsolator->getFixtureFiles($event->getFeature()->getTags());
        foreach ($fixtureFiles as $fixtureFile) {
            try {
                $file = $this->fixtureLoader->findFile($fixtureFile);
                $this->parser->parse($file);
            } catch (\Exception $e) {
                $message = sprintf(
                    'Error while find and parse "%s" fixture'.PHP_EOL.
                    '   Suite: %s'.PHP_EOL.
                    '   Feature: %s'.PHP_EOL.
                    '   %s',
                    $fixtureFile,
                    $event->getSuite()->getName(),
                    $event->getFeature()->getFile(),
                    $e->getMessage()
                );
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
