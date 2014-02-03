<?php
namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Entity;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

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
        /** @var Channel $channel */
        $channel = $this->getMock('Oro\Bundle\IntegrationBundle\Entity\Channel');
        $formType = uniqid('AnyFormType');
        $css = uniqid('styles');
        $title = uniqid('title');
        $successMessage = uniqid('success message');

        $formEntity = new EmbeddedForm();
        $formEntity->setChannel($channel);
        $formEntity->setFormType($formType);
        $formEntity->setCss($css);
        $formEntity->setTitle($title);
        $formEntity->setSuccessMessage($successMessage);

        $this->assertSame($channel, $formEntity->getChannel());
        $this->assertEquals($formType, $formEntity->getFormType());
        $this->assertEquals($css, $formEntity->getCss());
        $this->assertEquals($title, $formEntity->getTitle());
        $this->assertEquals($successMessage, $formEntity->getSuccessMessage());
    }

}
