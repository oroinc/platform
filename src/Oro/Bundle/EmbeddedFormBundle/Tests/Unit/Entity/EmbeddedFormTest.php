<?php
namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Entity;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;

class EmbeddedFormTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmbeddedForm */
    protected $entity;

    protected function setUp()
    {
        $this->entity = new EmbeddedForm();
    }

    protected function tearDown()
    {
        unset($this->entity);
    }

    /**
     * @dataProvider getSetDataProvider
     */
    public function testGetSet($property, $value, $expected)
    {
        if ($value !== null) {
            call_user_func_array([$this->entity, 'set' . ucfirst($property)], [$value]);
        }
        $this->assertEquals($expected, call_user_func_array([$this->entity, 'get' . ucfirst($property)], []));
    }

    public function getSetDataProvider()
    {
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        return array(
            'owner' => array('owner', $organization, $organization),
        );
    }

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
