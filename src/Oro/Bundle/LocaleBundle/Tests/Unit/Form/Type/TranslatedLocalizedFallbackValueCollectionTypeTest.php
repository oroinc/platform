<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Form\Type\TranslatedLocalizedFallbackValueCollectionType;

use Oro\Component\Testing\Unit\EntityTrait;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\Translator;

class TranslatedLocalizedFallbackValueCollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var TranslatedLocalizedFallbackValueCollectionType */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock(Translator::class, [], [], '', false);

        $this->type = new TranslatedLocalizedFallbackValueCollectionType($this->translator);
    }

    public function testGetName()
    {
        $this->assertEquals(TranslatedLocalizedFallbackValueCollectionType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(LocalizedFallbackValueCollectionType::class, $this->type->getParent());
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, [$this->type, 'translateFallbackValues']);

        $this->type->buildForm($builder, []);
    }

    /**
     * @dataProvider translateProvider
     *
     * @param array  $fallbackValues
     * @param string $field
     * @param array  $translated
     * @param array  $result
     */
    public function testTranslateFallbackValues(array $fallbackValues, $field, array $translated, array $result)
    {
        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->getMock(FormConfigInterface::class);
        $config->expects($this->once())
            ->method('getOption')
            ->with('field')
            ->will($this->returnValue($field));

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock(FormEvent::class, [], [], '', false);
        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(new ArrayCollection($fallbackValues)));
        $event->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));

        $valueMap = [];
        /** @var LocalizedFallbackValue $fallbackValue */
        foreach ($fallbackValues as $key => $fallbackValue) {
            if (!$fallbackValue->getId()) {
                $str = $field === 'string' ? $fallbackValue->getString() : $fallbackValue->getText();
                $res = array_key_exists($key, $translated) ? $translated[$key] : $str;
                $valueMap[] = [$str, [], null, $key, $res];
            }
        }

        $this->translator
            ->expects($this->exactly(count($valueMap)))
            ->method('trans')
            ->will($this->returnValueMap($valueMap));

        $this->type->translateFallbackValues($event);

        $this->assertEquals($result, $fallbackValues);
    }

    public function testTranslateFallbackValuesWithNonCollection()
    {
        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock(FormEvent::class, [], [], '', false);
        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('string'));
        $event->expects($this->never())
            ->method('getForm');

        $this->translator
            ->expects($this->never())
            ->method('trans');

        $this->type->translateFallbackValues($event);
    }

    public function testTranslateFallbackValuesInvalidArgumentException()
    {
        /** @var FormConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->getMock(FormConfigInterface::class);
        $config->expects($this->once())
            ->method('getOption')
            ->with('field')
            ->will($this->returnValue('string'));

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($config));

        /** @var FormEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock(FormEvent::class, [], [], '', false);
        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(new ArrayCollection(['test'])));
        $event->expects($this->once())
            ->method('getForm')
            ->will($this->returnValue($form));

        $this->translator
            ->expects($this->never())
            ->method('trans');

        $this->setExpectedException(
            \InvalidArgumentException::class,
            sprintf('ArrayCollection must contain only "%s"', LocalizedFallbackValue::class)
        );

        $this->type->translateFallbackValues($event);
    }

    /**
     * @return array
     */
    public function translateProvider()
    {
        return [
            'without translations' => [
                'fallback values' => [
                    'en' => $this->getLocalizedFallbackValue('en', 'string', 'en.test.string'),
                    'fr' => $this->getLocalizedFallbackValue('fr', 'string', 'fr.test.string'),
                    'de' => $this->getLocalizedFallbackValue('de', 'string', 'de.test.string'),
                ],
                'field' => 'string',
                'translated' => [],
                'result' => [
                    'en' => $this->getLocalizedFallbackValue('en', 'string', 'en.test.string'),
                    'fr' => $this->getLocalizedFallbackValue('fr', 'string', 'fr.test.string'),
                    'de' => $this->getLocalizedFallbackValue('de', 'string', 'de.test.string'),
                ]
            ],
            'with few translations' => [
                'fallback values' => [
                    'en' => $this->getLocalizedFallbackValue('en', 'string', 'en.test.string'),
                    'fr' => $this->getLocalizedFallbackValue('fr', 'string', 'fr.test.string'),
                    'de' => $this->getLocalizedFallbackValue('de', 'string', 'de.test.string'),
                ],
                'field' => 'string',
                'translated' => [
                    'fr' => 'FR Test String',
                    'de' => 'DE Test String',
                ],
                'result' => [
                    'en' => $this->getLocalizedFallbackValue('en', 'string', 'en.test.string'),
                    'fr' => $this->getLocalizedFallbackValue('fr', 'string', 'FR Test String'),
                    'de' => $this->getLocalizedFallbackValue('de', 'string', 'DE Test String'),
                ]
            ],
            'with all translations and few created fallback values' => [
                'fallback values' => [
                    'en' => $this->getLocalizedFallbackValue('en', 'string', 'en.test.string'),
                    'fr' => $this->getLocalizedFallbackValue('fr', 'string', 'fr.test.string', 1),
                    'de' => $this->getLocalizedFallbackValue('de', 'string', 'de.test.string', 2),
                ],
                'field' => 'string',
                'translated' => [
                    'en' => 'EN Test String',
                    'fr' => 'FR Test String',
                    'de' => 'DE Test String',
                ],
                'result' => [
                    'en' => $this->getLocalizedFallbackValue('en', 'string', 'EN Test String'),
                    'fr' => $this->getLocalizedFallbackValue('fr', 'string', 'fr.test.string', 1),
                    'de' => $this->getLocalizedFallbackValue('de', 'string', 'de.test.string', 2),
                ]
            ],
            'with all translations' => [
                'fallback values' => [
                    'en' => $this->getLocalizedFallbackValue('en', 'string', 'en.test.string'),
                    'fr' => $this->getLocalizedFallbackValue('fr', 'string', 'fr.test.string'),
                    'de' => $this->getLocalizedFallbackValue('de', 'string', 'de.test.string'),
                ],
                'field' => 'string',
                'translated' => [
                    'en' => 'EN Test String',
                    'fr' => 'FR Test String',
                    'de' => 'DE Test String',
                ],
                'result' => [
                    'en' => $this->getLocalizedFallbackValue('en', 'string', 'EN Test String'),
                    'fr' => $this->getLocalizedFallbackValue('fr', 'string', 'FR Test String'),
                    'de' => $this->getLocalizedFallbackValue('de', 'string', 'DE Test String'),
                ]
            ],
            'with all translations text' => [
                'fallback values' => [
                    'en' => $this->getLocalizedFallbackValue('en', 'text', 'en.test.text'),
                    'fr' => $this->getLocalizedFallbackValue('fr', 'text', 'fr.test.text'),
                    'de' => $this->getLocalizedFallbackValue('de', 'text', 'de.test.text'),
                ],
                'field' => 'text',
                'translated' => [
                    'en' => 'EN Test Text',
                    'fr' => 'FR Test Text',
                    'de' => 'DE Test Text',
                ],
                'result' => [
                    'en' => $this->getLocalizedFallbackValue('en', 'text', 'EN Test Text'),
                    'fr' => $this->getLocalizedFallbackValue('fr', 'text', 'FR Test Text'),
                    'de' => $this->getLocalizedFallbackValue('de', 'text', 'DE Test Text'),
                ]
            ]
        ];
    }

    /**
     * @param string $formattingCode
     * @param string $field
     * @param string $value
     * @param int|null $id
     *
     * @return LocalizedFallbackValue
     */
    protected function getLocalizedFallbackValue($formattingCode, $field, $value, $id = null)
    {
        $localization = new Localization();
        $localization->setFormattingCode($formattingCode);

        $properties = [
            'id' => $id,
            'localization' => $localization
        ];

        if ($field === 'string') {
            $properties['string'] = $value;
        } else {
            $properties['text'] = $value;
        }

        return $this->getEntity(LocalizedFallbackValue::class, $properties);
    }
}
