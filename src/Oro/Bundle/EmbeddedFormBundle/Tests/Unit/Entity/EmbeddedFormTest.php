<?php
namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Entity;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;

class EmbeddedFormTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructed()
    {
        new EmbeddedForm();
    }

    /**
     * @test
     */
    public function shouldSetEntityPropertiesAndReturnBack()
    {
        $formType = uniqid('AnyFormType');
        $css = uniqid('styles');
        $title = uniqid('title');
        $successMessage = uniqid('success message');

        $formEntity = new EmbeddedForm();
        $formEntity->setFormType($formType);
        $formEntity->setCss($css);
        $formEntity->setTitle($title);
        $formEntity->setSuccessMessage($successMessage);

        $this->assertEquals($formType, $formEntity->getFormType());
        $this->assertEquals($css, $formEntity->getCss());
        $this->assertEquals($title, $formEntity->getTitle());
        $this->assertEquals($successMessage, $formEntity->getSuccessMessage());
    }
}
