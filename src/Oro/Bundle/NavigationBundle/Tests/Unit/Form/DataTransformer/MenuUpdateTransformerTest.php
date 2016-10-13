<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Form\DataTransformer\MenuUpdateTransformer;

use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;

use Symfony\Component\Translation\TranslatorInterface;

class MenuUpdateTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationHelper;

    /** @var MenuUpdateTransformer */
    protected $transformer;

    protected function setUp()
    {
        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->localizationHelper = $this->getMock(LocalizationHelper::class, [], [], '', false);

        $this->transformer = new MenuUpdateTransformer($this->translator, $this->localizationHelper);
    }

    public function testTransformWithoutDefaultTitle()
    {
        $menuUpdate = new MenuUpdateStub();
        $menuUpdate->setDefaultTitle('default.title');

        $this->localizationHelper
            ->expects($this->never())
            ->method('getLocalizations');

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('default.title', [], null, null)
            ->will($this->returnValue('default.title'));

        $this->transformer->transform($menuUpdate);

        $this->assertEquals(0, $menuUpdate->getTitles()->count());
    }

    /**
     * @dataProvider transformProvider
     *
     * @param array $default
     * @param array $translated
     * @param array $result
     */
    public function testTransform(array $default, array $translated, array $result)
    {
        $menuUpdate = new MenuUpdateStub();
        $menuUpdate->setDefaultTitle($default['key']);

        $valueMap = [[$default['key'], [], null, null, $default['value']]];

        $localizations = [];

        foreach ($translated as $locale => $value) {
            $localization = new Localization();
            $localization->setLanguageCode($locale);

            $localizations[] = $localization;
            $valueMap[] = ['default.title', [], null, $locale, $value];
        }

        $this->localizationHelper
            ->expects($this->once())
            ->method('getLocalizations')
            ->will($this->returnValue($localizations));


        $this->translator
            ->expects($this->exactly(count($valueMap)))
            ->method('trans')
            ->will($this->returnValueMap($valueMap));

        $this->transformer->transform($menuUpdate);

        $this->assertEquals(count($result), $menuUpdate->getTitles()->count());

        $i = 0;
        foreach ($result as $value) {
            $this->assertEquals($value, $menuUpdate->getTitles()->get($i++));
        }
    }

    public function testReverseTransform()
    {
        $menuUpdate = new MenuUpdateStub();

        $this->assertEquals($menuUpdate, $this->transformer->reverseTransform($menuUpdate));
    }


    /**
     * @return array
     */
    public function transformProvider()
    {
        return [
            'without translations' => [
                'default' => [
                    'key' => 'default.title',
                    'value' => 'Default Value',
                ],
                'translated' => [],
                'result' => []
            ],
            'with few translations' => [
                'default' => [
                    'key' => 'default.title',
                    'value' => 'Default Value',
                ],
                'translated' => [
                    'en' => 'EN Default Value',
                    'fr' => 'default.title',
                    'de' => 'DE Default Value',
                ],
                'result' => [
                    'en value' => 'EN Default Value',
                    'de value' => 'DE Default Value',
                ]
            ],
            'with all translations' => [
                'default' => [
                    'key' => 'default.title',
                    'value' => 'Default Value',
                ],
                'translated' => [
                    'en' => 'EN Default Value',
                    'fr' => 'FR Default Value',
                    'de' => 'DE Default Value',
                ],
                'result' => [
                    'en value' => 'EN Default Value',
                    'fr value' => 'FR Default Value',
                    'de value' => 'DE Default Value',
                ]
            ]
        ];
    }
}
