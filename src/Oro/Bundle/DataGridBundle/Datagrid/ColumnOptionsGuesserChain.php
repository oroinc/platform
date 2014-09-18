<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Symfony\Component\Form\Guess\Guess;

use Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException;

class ColumnOptionsGuesserChain implements ColumnOptionsGuesserInterface
{
    /** @var array  */
    protected $guessers = [];

    /**
     * Constructor.
     *
     * @param ColumnOptionsGuesserInterface[] $guessers Guessers as instances of ColumnOptionsGuesserInterface
     *
     * @throws UnexpectedTypeException if any guesser does not implement ColumnOptionsGuesserInterface
     */
    public function __construct(array $guessers)
    {
        foreach ($guessers as $guesser) {
            if (!$guesser instanceof ColumnOptionsGuesserInterface) {
                throw new UnexpectedTypeException(
                    $guesser,
                    'Oro\Bundle\DataGridBundle\Datagrid\ColumnOptionsGuesserInterface'
                );
            }

            if ($guesser instanceof self) {
                $this->guessers = array_merge($this->guessers, $guesser->guessers);
            } else {
                $this->guessers[] = $guesser;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function guessFormatter($class, $property, $type)
    {
        return $this->guess(
            function ($guesser) use ($class, $property, $type) {
                return $guesser->guessFormatter($class, $property, $type);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function guessSorter($class, $property, $type)
    {
        return $this->guess(
            function ($guesser) use ($class, $property, $type) {
                return $guesser->guessSorter($class, $property, $type);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function guessFilter($class, $property, $type)
    {
        return $this->guess(
            function ($guesser) use ($class, $property, $type) {
                return $guesser->guessFilter($class, $property, $type);
            }
        );
    }

    /**
     * Executes a closure for each guesser and returns the best guess from the
     * return values
     *
     * @param \Closure $closure The closure to execute. Accepts a guesser
     *                          as argument and should return a Guess instance
     *
     * @return Guess|null The guess with the highest confidence
     */
    protected function guess(\Closure $closure)
    {
        $guesses = array();

        foreach ($this->guessers as $guesser) {
            $guess = $closure($guesser);
            if ($guess) {
                $guesses[] = $guess;
            }
        }

        return Guess::getBestGuess($guesses);
    }
}
