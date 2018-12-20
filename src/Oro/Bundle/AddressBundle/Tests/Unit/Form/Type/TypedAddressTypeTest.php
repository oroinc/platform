<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\AddressBundle\Form\Type\TypedAddressType;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class TypedAddressTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TypedAddressType
     */
    protected $type;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->type = new TypedAddressType();
    }

    /**
     * @dataProvider buildFormDataProvider
     *
     * @param array $options
     * @param bool $expectAddSubscriber
     */
    public function testBuildForm(array $options, $expectAddSubscriber)
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $at = 0;

        if ($expectAddSubscriber) {
            $builder->expects($this->at($at++))
                ->method('addEventSubscriber')
                ->with(
                    $this->isInstanceOf(
                        'Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesPrimarySubscriber'
                    )
                )
                ->will($this->returnSelf());

            $builder->expects($this->at($at++))
                ->method('addEventSubscriber')
                ->with(
                    $this->isInstanceOf(
                        'Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesTypesSubscriber'
                    )
                )
                ->will($this->returnSelf());
        }

        $builder->expects($this->at($at++))
            ->method('add')
            ->with(
                'types',
                TranslatableEntityType::class,
                array(
                    'class'    => 'OroAddressBundle:AddressType',
                    'choice_label' => 'label',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true,
                )
            )
            ->will($this->returnSelf());

        $builder->expects($this->at($at++))
            ->method('add')
            ->with(
                'primary',
                CheckboxType::class,
                array(
                    'required' => false
                )
            )
            ->will($this->returnSelf());

        $this->type->buildForm($builder, $options);
    }

    public function buildFormDataProvider()
    {
        return array(
            array(
                'options' => array(
                    'single_form' => false,
                    'all_addresses_property_path' => null,
                ),
                'expectAddSubscriber' => false
            ),
            array(
                'options' => array(
                    'single_form' => true,
                    'all_addresses_property_path' => null,
                ),
                'expectAddSubscriber' => false
            ),
            array(
                'options' => array(
                    'single_form' => true,
                    'all_addresses_property_path' => 'owner.addresses',
                ),
                'expectAddSubscriber' => true
            )
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(AddressType::class, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_typed_address', $this->type->getName());
    }
}
