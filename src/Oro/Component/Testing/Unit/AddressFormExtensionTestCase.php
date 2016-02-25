<?php

namespace Oro\Component\Testing\Unit;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\FormBundle\Form\Extension\RandomIdExtension;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;

abstract class AddressFormExtensionTestCase extends FormIntegrationTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatableEntityType $registry */
        $translatableEntity = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->setMethods(['setDefaultOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

        $country = new Country('US');
        $choices = [
            'OroAddressBundle:Country' => ['US' => $country],
            'OroAddressBundle:Region' => ['US-AL' => (new Region('US-AL'))->setCountry($country)],
        ];

        $translatableEntity->expects($this->any())->method('setDefaultOptions')->will(
            $this->returnCallback(
                function (OptionsResolver $resolver) use ($choices) {
                    $choiceList = function (Options $options) use ($choices) {
                        $className = $options->offsetGet('class');
                        if (array_key_exists($className, $choices)) {
                            return new ArrayChoiceList(
                                $choices[$className],
                                function ($item) {
                                    if ($item instanceof Country) {
                                        return $item->getIso2Code();
                                    }

                                    if ($item instanceof Region) {
                                        return $item->getCombinedCode();
                                    }

                                    return $item . uniqid('form', true);
                                }
                            );
                        }

                        return new ArrayChoiceList([]);
                    };

                    $resolver->setDefault('choice_list', $choiceList);
                }
            )
        );

        return [
            new PreloadedExtension(
                [
                    'oro_address' => new AddressType(new AddressCountryAndRegionSubscriberStub()),
                    'oro_country' => new CountryType(),
                    'genemu_jqueryselect2_translatable_entity' => new Select2Type('translatable_entity'),
                    'genemu_jqueryselect2_choice' => new Select2Type('choice'),
                    'translatable_entity' => $translatableEntity,
                    'oro_region' => new RegionType(),
                ],
                ['form' => [new RandomIdExtension()]]
            )
        ];
    }
}
