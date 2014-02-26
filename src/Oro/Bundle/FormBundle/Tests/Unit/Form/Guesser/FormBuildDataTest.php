<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Guesser;

use Oro\Bundle\FormBundle\Guesser\FormBuildData;

class FormBuildDataTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorAndGetters()
    {
        $formType = 'test_form_type';
        $formOptions = array('test' => 'options');
        $formBuildData = new FormBuildData($formType, $formOptions);

        $this->assertEquals($formType, $formBuildData->getFormType());
        $this->assertEquals($formOptions, $formBuildData->getFormOptions());
    }
}
