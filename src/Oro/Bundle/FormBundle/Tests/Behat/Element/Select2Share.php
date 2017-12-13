<?php

namespace Oro\Bundle\FormBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\FormBundle\Tests\Behat\Context\ClearableInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\UIBundle\Tests\Behat\Element\UiDialog;
use WebDriver\Key;

class Select2Share extends Select2Entity
{
    /**
     * @var string
     */
    protected $searchInputSelector = '.select2-search-field input';
}
