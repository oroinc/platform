<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Nelmio\Alice\Instances\Processor\Methods\MethodInterface;
use Nelmio\Alice\Instances\Processor\Processable;
use Nelmio\Alice\Instances\Processor\ProcessableInterface;
use Nelmio\Alice\Instances\Processor\Processor;

class AliceToStringProcessor implements MethodInterface
{
    private static $regex = '/^<toString\((?P<value>[^<]*)\)>$/';

    /** @var Processor */
    protected $processor;

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
            && $processable->valueMatches(static::$regex);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ProcessableInterface $processable, array $variables)
    {
        $value = $processable->getMatch('value');
        $value = $this->processor->process(new Processable($value), $variables);
        if (null !== $value) {
            $value = $this->convertValueToString($value);
        }

        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function convertValueToString($value)
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('c');
        }

        return (string)$value;
    }
}
