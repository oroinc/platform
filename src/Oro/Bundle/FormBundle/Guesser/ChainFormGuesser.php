<?php

namespace Oro\Bundle\FormBundle\Guesser;

class ChainFormGuesser implements FormGuesserInterface
{
    /**
     * @var FormGuesserInterface[]
     */
    protected $guessers = array();

    /**
     * @param FormGuesserInterface $guesser
     */
    public function addGuesser(FormGuesserInterface $guesser)
    {
        $this->guessers[] = $guesser;
    }

    /**
     * {@inheritDoc}
     */
    public function guess($class, $field = null)
    {
        foreach ($this->guessers as $guesser) {
            $formBuildData = $guesser->guess($class, $field);
            if ($formBuildData) {
                return $formBuildData;
            }
        }

        return null;
    }
}
