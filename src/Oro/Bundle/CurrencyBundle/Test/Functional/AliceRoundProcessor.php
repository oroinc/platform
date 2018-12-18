<?php

namespace Oro\Bundle\CurrencyBundle\Test\Functional;

use Nelmio\Alice\Instances\Processor\Methods\MethodInterface;
use Nelmio\Alice\Instances\Processor\Processable;
use Nelmio\Alice\Instances\Processor\ProcessableInterface;
use Nelmio\Alice\Instances\Processor\Processor;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;

/**
 * This processor can be used to add rounding functions based on services that implement RoundingServiceInterface.
 */
class AliceRoundProcessor implements MethodInterface
{
    /** @var RoundingServiceInterface */
    protected $roundingService;

    /** @var string */
    private $functionName;

    /** @var Processor */
    private $processor;

    /**
     * @param string                   $functionName
     * @param RoundingServiceInterface $roundingService
     */
    public function __construct(string $functionName, RoundingServiceInterface $roundingService)
    {
        $this->functionName = $functionName;
        $this->roundingService = $roundingService;
    }

    /**
     * @param Processor $processor
     */
    public function setProcessor(Processor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function canProcess(ProcessableInterface $processable)
    {
        return
            is_string($processable->getValue())
            && $processable->valueMatches('/^<' . $this->functionName . '\((?P<value>[^<]*)\)>$/');
    }

    /**
     * {@inheritdoc}
     */
    public function process(ProcessableInterface $processable, array $variables)
    {
        $value = $processable->getMatch('value');
        $value = $this->processor->process(new Processable($value), $variables);
        if (null !== $value) {
            $value = $this->round($value);
        }

        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected function round($value)
    {
        return $this->roundingService->round($value);
    }
}
