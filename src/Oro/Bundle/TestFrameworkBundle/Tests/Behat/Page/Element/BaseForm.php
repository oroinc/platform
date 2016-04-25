<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Behat\Page\Element;

use Behat\Gherkin\Node\TableNode;
use SensioLabs\Behat\PageObjectExtension\PageObject\Element;
use SensioLabs\Behat\PageObjectExtension\PageObject\Exception\PathNotProvidedException;
use SensioLabs\Behat\PageObjectExtension\PageObject\Page;

abstract class BaseForm extends Element
{
    /**
     * @var array|null
     */
    protected $fieldsMap = null;

    /**
     * @param TableNode $table
     *
     * @return Page
     */
    public function fill(TableNode $table)
    {
        foreach ($table->getRows() as $row) {
            $this->fillField($this->getFieldName($row[0]), $row[1]);
        }
    }

    protected function getFieldName($key)
    {
        if (false === is_array($this->fieldsMap)) {
            throw new PathNotProvidedException('You must add a fieldsMap property to your form element');
        } elseif (false === isset($this->fieldsMap[strtolower($key)])) {
            throw new \InvalidArgumentException(sprintf(
                '"%s" key not found in fieldsMap in "%s" class',
                $key,
                __CLASS__
            ));
        }

        return $this->fieldsMap[strtolower($key)];
    }
}
