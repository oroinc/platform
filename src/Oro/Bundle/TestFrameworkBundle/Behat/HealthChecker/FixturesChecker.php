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
     * @var array
     */
    protected $errors = [];

    public function __construct(FixtureLoader $fixtureLoader, OroYamlParser $parser)
    {
        $this->fixtureLoader = $fixtureLoader;
        $this->parser = $parser;
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

    public function checkFixtures(BeforeFeatureTested $event)
    {
        $fixtureFiles = DoctrineIsolator::getFixtureFiles($event->getFeature()->getTags());
        foreach ($fixtureFiles as $fixtureFile) {
            try {
                $file = $this->fixtureLoader->findFile($fixtureFile);
                $this->parser->parse($file);
            } catch (\Exception $e) {
                $this->addError($e->getMessage());
            }
        }
    }

    public function isFailure()
    {
        return !empty($this->errors);
    }

    private function addError($message)
    {
        $this->errors[] = $message;
    }

    /**
     * Return array of strings error messages
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}