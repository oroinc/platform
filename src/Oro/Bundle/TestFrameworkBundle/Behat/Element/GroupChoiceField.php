<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Session;

class GroupChoiceField extends Element
{
    /**
     * @var array
     */
    protected $choices;

    /**
     * {@inheritdoc}
     */
    public function __construct(Session $session, OroElementFactory $elementFactory, $selector = ['xpath' => '//'])
    {
        parent::__construct($session, $elementFactory, $selector);

        $this->choices = $this->findChoices();
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($values)
    {
        $values = true === is_array($values) ? $values : [$values];

        foreach ($values as $label) {
            $checkbox = $this->getCheckbox($label);
            self::assertNotNull($checkbox, sprintf('Can\'t find checkbox with "%s" label', $label));
            $this->getCheckbox($label)->check();
        }
    }

    /**
     * Find all checkboxes and return them with label as array like:
     * array('Label Name' => NodeElement)
     * @return NodeElement[]
     */
    protected function findChoices()
    {
        $checkboxes = $this->findAll('css', 'input[type=checkbox]');
        self::assertNotCount(0, $checkboxes, 'Not found any checkboxes in GroupChoiceField');
        $choices = [];

        /** @var NodeElement $checkbox */
        foreach ($checkboxes as $checkbox) {
            $label = $this->find('css', sprintf('label[for=%s]', $checkbox->getAttribute('id')));
            self::assertNotNull($label, 'Can\'t find label for checkbox');

            $choices[$label->getText()] = $checkbox;
        }

        return $choices;
    }

    /**
     * @param string $label
     * @return NodeElement|null
     */
    protected function getCheckbox($label)
    {
        $choices = array_intersect_key(
            $this->choices,
            array_flip(preg_grep(sprintf('/%s/i', $label), array_keys($this->choices)))
        );
        self::assertCount(1, $choices, sprintf('Too many results for "%s" label', $label));

        return array_shift($choices);
    }
}
