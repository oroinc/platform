<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class EmbeddedForms
 * @package Oro\Bundle\EmbeddedFormBundle\Tests\Selenium\Pages
 * @method EmbeddedForms openEmbeddedForms openEmbeddedForms(string)
 * {@inheritdoc}
 */
class EmbeddedForms extends AbstractPageFilteredGrid
{
    const URL = 'embedded-form';

    public function __construct($testCase, $redirect = true)
    {
        $this->redirectUrl = self::URL;
        parent::__construct($testCase, $redirect);
    }

    /**
     * @return EmbeddedForm
     */
    public function add()
    {
        $this->test->byXPath("//a[@title='Create Embedded Form']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new EmbeddedForm($this->test);
    }

    /**
     * @param array $entityData
     * @return EmbeddedForm
     */
    public function open($entityData = array())
    {
        $form = $this->getEntity($entityData, 1);
        $form->click();
        sleep(1);
        $this->waitPageToLoad();
        $this->waitForAjax();

        return new EmbeddedForm($this->test);
    }
}
