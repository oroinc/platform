<?php
namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Entity;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedFormEntity;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class EmbeddedFormEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructed()
    {
        new EmbeddedFormEntity();
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

        $formEntity = new EmbeddedFormEntity();
        $formEntity->setChannel($channel);
        $formEntity->setFormType($formType);
        $formEntity->setCss($css);
        $formEntity->setTitle($title);

        $this->assertSame($channel, $formEntity->getChannel());
        $this->assertEquals($formType, $formEntity->getFormType());
        $this->assertEquals($css, $formEntity->getCss());
        $this->assertEquals($title, $formEntity->getTitle());

    }

}
