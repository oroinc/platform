<?php

namespace Oro\Bundle\TagBundle\Tests\Behat\Element;

use Oro\Bundle\CalendarBundle\Tests\Behat\Element\ColorsAwareInterface;
use Oro\Bundle\CalendarBundle\Tests\Behat\Element\EventColors;
use Oro\Bundle\FormBundle\Tests\Behat\Element\OroForm;

class TaxonomyForm extends OroForm implements ColorsAwareInterface
{
    use EventColors;
}
