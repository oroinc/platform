<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Cli\Stub;

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker\HealthCheckerInterface;

class HealthCheckerStub implements HealthCheckerInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $errors;

    public function __construct(string $name, array $errors = [])
    {
        $this->name = $name;
        $this->errors = $errors;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeFeatureTested::BEFORE => 'check'
        ];
    }

    public function check()
    {
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
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }
}
