<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\EventListener;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Form\Test\FormInterface;

class AddressCountryAndRegionSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_COUNTRY_NAME = 'testCountry';

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $om;

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formBuilder;

    /** @var AddressCountryAndRegionSubscriber */
    private $subscriber;

    protected function setUp(): void
    {
        $this->om = $this->createMock(ObjectManager::class);
        $this->formBuilder = $this->createMock(FormFactoryInterface::class);

        $this->subscriber = new AddressCountryAndRegionSubscriber($this->om, $this->formBuilder);
    }

    public function testGetSubscribedEvents()
    {
        $result = $this->subscriber->getSubscribedEvents();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $result);
        $this->assertArrayHasKey(FormEvents::PRE_SUBMIT, $result);
    }

    public function testPreSetDataEmptyAddress()
    {
        $eventMock = $this->createMock(FormEvent::class);

        $eventMock->expects($this->once())
            ->method('getData')
            ->willReturn(null);
        $eventMock->expects($this->once())
            ->method('getForm');

        $this->subscriber->preSetData($eventMock);
    }

    public function testPreSetDataEmptyCountry()
    {
        $eventMock = $this->createMock(FormEvent::class);

        $addressMock = $this->createMock(Address::class);
        $addressMock->expects($this->once())
            ->method('getCountry')
            ->willReturn(null);

        $eventMock->expects($this->once())
            ->method('getData')
            ->willReturn($addressMock);
        $eventMock->expects($this->once())
            ->method('getForm');

        $this->subscriber->preSetData($eventMock);
    }

    public function testPreSetDataHasRegion()
    {
        $eventMock = $this->createMock(FormEvent::class);

        $countryMock = $this->createMock(Country::class);
        $countryMock->expects($this->once())
            ->method('hasRegions')
            ->willReturn(true);

        $addressMock = $this->createMock(Address::class);
        $addressMock->expects($this->once())
            ->method('getCountry')
            ->willReturn($countryMock);
        $addressMock->expects($this->once())
            ->method('getRegion');

        $configMock = $this->createMock(FormConfigInterface::class);
        $configMock->expects($this->once())
            ->method('getOptions')
            ->willReturn([]);
        $type = $this->createMock(ResolvedFormTypeInterface::class);
        $configMock->expects($this->any())
            ->method('getType')
            ->willReturn($type);
        $type->expects($this->once())
            ->method('getInnerType')
            ->willReturn(new SubmitType());

        $fieldMock = $this->createMock(FormInterface::class);

        $formMock = $this->createMock(FormInterface::class);
        $formMock->expects($this->once())
            ->method('has')
            ->with('region')
            ->willReturn(true);
        $formMock->expects($this->once())
            ->method('get')
            ->with('region')
            ->willReturn($fieldMock);
        $formMock->expects($this->once())
            ->method('add');

        $fieldMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        $newFieldMock = $this->createMock(FormInterface::class);

        $this->formBuilder->expects($this->once())
            ->method('createNamed')
            ->willReturn($newFieldMock);

        $eventMock->expects($this->once())
            ->method('getData')
            ->willReturn($addressMock);
        $eventMock->expects($this->once())
            ->method('getForm')
            ->willReturn($formMock);

        $this->subscriber->preSetData($eventMock);
    }

    public function testPreSetDataNoRegion()
    {
        $eventMock = $this->createMock(FormEvent::class);

        $countryMock = $this->createMock(Country::class);
        $countryMock->expects($this->once())
            ->method('hasRegions')
            ->willReturn(true);

        $addressMock = $this->createMock(Address::class);
        $addressMock->expects($this->once())
            ->method('getCountry')
            ->willReturn($countryMock);
        $addressMock->expects($this->once())
            ->method('getRegion');

        $formMock = $this->createMock(FormInterface::class);
        $formMock->expects($this->once())
            ->method('has')
            ->with('region')
            ->willReturn(false);
        $formMock->expects($this->never())
            ->method('get');
        $formMock->expects($this->once())
            ->method('add');

        $newFieldMock = $this->createMock(FormInterface::class);

        $this->formBuilder->expects($this->once())
            ->method('createNamed')
            ->willReturn($newFieldMock);

        $eventMock->expects($this->once())
            ->method('getData')
            ->willReturn($addressMock);
        $eventMock->expects($this->once())
            ->method('getForm')
            ->willReturn($formMock);

        $this->subscriber->preSetData($eventMock);
    }

    public function testPreSubmitData()
    {
        $eventMock = $this->createMock(FormEvent::class);

        $countryMock = $this->createMock(Country::class);
        $countryMock->expects($this->once())
            ->method('hasRegions')
            ->willReturn(true);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->with(self::TEST_COUNTRY_NAME)
            ->willReturn($countryMock);

        $this->om->expects($this->once())
            ->method('getRepository')
            ->with('OroAddressBundle:Country')
            ->willReturn($repository);

        $configMock = $this->createMock(FormConfigInterface::class);
        $configMock->expects($this->once())
            ->method('getOptions')
            ->willReturn([]);
        $type = $this->createMock(ResolvedFormTypeInterface::class);
        $configMock->expects($this->any())
            ->method('getType')
            ->willReturn($type);
        $type->expects($this->once())
            ->method('getInnerType')
            ->willReturn(new SubmitType());

        $fieldMock = $this->createMock(FormInterface::class);
        $fieldMock->expects($this->atLeastOnce())
            ->method('getConfig')
            ->willReturn($configMock);

        $formMock = $this->createMock(FormInterface::class);
        $formMock->expects($this->atLeastOnce())
            ->method('get')
            ->with('region')
            ->willReturn($fieldMock);
        $formMock->expects($this->once())
            ->method('add');

        $newFieldMock = $this->createMock(FormInterface::class);

        $this->formBuilder->expects($this->once())
            ->method('createNamed')
            ->willReturn($newFieldMock);

        $startData = ['region_text' => 'regionText', 'country' => self::TEST_COUNTRY_NAME];
        $eventMock->expects($this->once())
            ->method('getData')
            ->willReturn($startData);
        $eventMock->expects($this->once())
            ->method('getForm')
            ->willReturn($formMock);

        $eventMock->expects($this->once())
            ->method('setData')
            ->with(array_intersect_key($startData, ['country' => self::TEST_COUNTRY_NAME]));

        $this->subscriber->preSubmit($eventMock);
    }

    /**
     * Cover scenario when country has not any stored region and region filled as text field
     */
    public function testPreSubmitDataTextScenario()
    {
        $eventMock = $this->createMock(FormEvent::class);

        $countryMock = $this->createMock(Country::class);
        $countryMock->expects($this->once())
            ->method('hasRegions')
            ->willReturn(false);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->with(self::TEST_COUNTRY_NAME)
            ->willReturn($countryMock);

        $this->om->expects($this->once())
            ->method('getRepository')
            ->with('OroAddressBundle:Country')
            ->willReturn($repository);

        $startData = [
            'region' => 'someRegion', 'region_text' => 'regionText', 'country' => self::TEST_COUNTRY_NAME
        ];
        $eventMock->expects($this->once())
            ->method('getData')
            ->willReturn($startData);

        $eventMock->expects($this->once())
            ->method('setData')
            ->with(array_intersect_key($startData, ['region_text' => null, 'country' => self::TEST_COUNTRY_NAME]));

        $this->subscriber->preSubmit($eventMock);
    }
}
