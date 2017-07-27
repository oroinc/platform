<?php

namespace Oro\Bundle\BatchBundle\Tests\Functional\ORM\Constraint;

use PHPUnit_Framework_Constraint;

/**
 * Compares datasets expecting each item will contain 'id' field
 */
class IsEqualById extends PHPUnit_Framework_Constraint
{
    /**
     * @var mixed
     */
    protected $value;


    /**
     * @inheritdoc
     */
    public function __construct($value)
    {
        parent::__construct();

        $this->value        = $value;
    }

    /**
     * @inheritdoc
     */
    public function evaluate($other, $description = '', $returnResult = false)
    {
        if ($this->value === $other) {
            return true;
        }

        return array_udiff_assoc($this->value, $other, array($this, 'itemCompare'));
    }

    /**
     * Compares 2 items by ID
     *
     * @param $a
     * @param $b
     * @return mixed
     */
    protected function itemCompare($a, $b)
    {
        return $this->getId($a) - $this->getId($b);
    }

    /**
     * Fetches item ID
     *
     * @param $item
     * @return int
     */
    protected function getId($item)
    {
        switch (true) {
            case (is_array($item)):
                $id = $item['id'];
                break;
            case (is_object($item)):
                $id = $item->getId();
                break;
            default:
                $id = $item;
        }

        return $id;
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString()
    {
        $delta = '';

        if (is_string($this->value)) {
            if (strpos($this->value, "\n") !== false) {
                return 'is equal to <text>';
            } else {
                return sprintf(
                    'is equal to <string:%s>',
                    $this->value
                );
            }
        } else {
            return sprintf(
                'is equal to %s%s',
                $this->exporter->export($this->value),
                $delta
            );
        }
    }
}
