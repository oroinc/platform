<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class EmbeddedForms
 * @package Oro\Bundle\EmbeddedFormBundle\Tests\Selenium\Pages
 * @method EmbeddedForms openEmbeddedForms(string $bundlePath)
 * @method EmbeddedForm add()
 * @method EmbeddedForm open(array $filter)
 * {@inheritdoc}
 */
class EmbeddedForms extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Create Embedded Form']";
    const URL = 'embedded-form';

    public function entityNew()
    {
        return new EmbeddedForm($this->test);
    }

    public function entityView()
    {
        return new EmbeddedForm($this->test);
    }
}
