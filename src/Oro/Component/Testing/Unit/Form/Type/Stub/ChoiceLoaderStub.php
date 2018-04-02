<?php

namespace Oro\Component\Testing\Unit\Form\Type\Stub;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

class ChoiceLoaderStub implements ChoiceLoaderInterface
{
    /**
     * @var ArrayChoiceList
     */
    private $choiceList;

    /**
     * @param array $choices
     */
    public function __construct(array $choices)
    {
        $this->choiceList = new ArrayChoiceList($choices, function ($givenChoice) use ($choices) {
            foreach ($choices as $value => $choice) {
                if ($choice === $givenChoice) {
                    return $value;
                }
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoiceList($value = null)
    {
        return $this->choiceList;
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoicesForValues(array $values, $value = null)
    {
        return $this->choiceList->getChoicesForValues($values);
    }

    /**
     * {@inheritdoc}
     */
    public function loadValuesForChoices(array $choices, $value = null)
    {
        return $this->choiceList->getValuesForChoices($choices);
    }
}
