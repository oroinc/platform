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

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeFeatureTested::BEFORE => 'check'
        ];
    }

    public function check()
    {
    }

    #[\Override]
    public function isFailure()
    {
        return !empty($this->errors);
    }

    #[\Override]
    public function getErrors()
    {
        return $this->errors;
    }

    #[\Override]
    public function getName()
    {
        return $this->name;
    }
}
