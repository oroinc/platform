<?php
namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormView;

class AddressTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AddressType
     */
    protected $type;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $buildAddressFormListener = $this->getMockBuilder(
            'Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber'
        )->disableOriginalConstructor()->getMock();

        $this->type = new AddressType($buildAddressFormListener);
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->exactly(15))
            ->method('add')
            ->will($this->returnSelf());

        $builder->expects($this->at(0))
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Symfony\Component\EventDispatcher\EventSubscriberInterface'))
            ->will($this->returnSelf());

        $builder->expects($this->at(1))
            ->method('add')
            ->with('id', HiddenType::class);

        $builder->expects($this->at(2))
            ->method('add')
            ->with('label', TextType::class);

        $builder->expects($this->at(3))
            ->method('add')
            ->with('namePrefix', TextType::class);

        $builder->expects($this->at(4))
            ->method('add')
            ->with('firstName', TextType::class);

        $builder->expects($this->at(5))
            ->method('add')
            ->with('middleName', TextType::class);

        $builder->expects($this->at(6))
            ->method('add')
            ->with('lastName', TextType::class);

        $builder->expects($this->at(7))
            ->method('add')
            ->with('nameSuffix', TextType::class);

        $builder->expects($this->at(8))
            ->method('add')
            ->with('organization', TextType::class);

        $builder->expects($this->at(9))
            ->method('add')
            ->with('country', CountryType::class);

        $builder->expects($this->at(10))
            ->method('add')
            ->with('street', TextType::class);

        $builder->expects($this->at(11))
            ->method('add')
            ->with('street2', TextType::class);

        $builder->expects($this->at(12))
            ->method('add')
            ->with('city', TextType::class);

        $builder->expects($this->at(13))
            ->method('add')
            ->with('region', RegionType::class);

        $builder->expects($this->at(14))
            ->method('add')
            ->with('region_text', HiddenType::class);

        $builder->expects($this->at(15))
            ->method('add')
            ->with('postalCode', TextType::class);

        $this->type->buildForm($builder, array());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->configureOptions($resolver);
    }

    public function testBuildView()
    {
        $view = new FormView();
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $this->type->buildView($view, $form, ['region_route' => 'test']);

        $this->assertArrayHasKey('region_route', $view->vars);
        $this->assertEquals('test', $view->vars['region_route']);
    }
}
