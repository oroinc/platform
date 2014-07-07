<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\TrackingBundle\Form\Type\TrackingWebsiteType;

class TrackingWebsiteTypeTest extends FormIntegrationTestCase
{
    /**
     * @var TrackingWebsiteType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new TrackingWebsiteType(
            'Oro\Bundle\TrackingBundle\Entity\TrackingWebsite'
        );
    }

    public function testBuildForm()
    {
        $builder = $this
            ->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder
            ->expects($this->exactly(3))
            ->method('add')
            ->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_tracking_website', $this->type->getName());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');

        $resolver
            ->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->setDefaultOptions($resolver);
    }
}
