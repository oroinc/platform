<?php
namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Form\Type\CountryType;

class CountryTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CountryType
     */
    protected $type;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->type = new CountryType(
            'Oro\Bundle\AddressBundle\Entity\Address',
            'Oro\Bundle\AddressBundle\Entity\Value\AddressValue'
        );
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver $resolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_select2_translatable_entity', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_country', $this->type->getName());
    }
}
