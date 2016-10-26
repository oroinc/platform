<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Helper;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Menu\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;

class MenuUpdateHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationHelper;

    /** @var MenuUpdateHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->localizationHelper = $this->getMock(LocalizationHelper::class, [], [], '', false);

        $this->helper = new MenuUpdateHelper($this->translator, $this->localizationHelper);
    }

    public function testApplyLocalizedFallbackValue()
    {
        $update = new MenuUpdateStub();

        $this->translator
            ->expects($this->exactly(3))
            ->method('trans')
            ->will($this->returnValueMap([
                ['test.title', [], null, null, 'Test Title'],
                ['test.title', [], null, 'en', 'Test Title'],
                ['test.title', [], null, 'de', 'DE Test Title'],
            ]));

        $enLocalization = new Localization();
        $enLocalization->setLanguageCode('en');

        $deLocalization = new Localization();
        $deLocalization->setLanguageCode('de');

        $this->localizationHelper
            ->expects($this->once())
            ->method('getLocalizations')
            ->will($this->returnValue([
                $enLocalization,
                $deLocalization
            ]));

        $this->helper->applyLocalizedFallbackValue($update, 'test.title', 'title', 'string');

        $deFallbackValue = new LocalizedFallbackValue();
        $deFallbackValue->setLocalization($deLocalization);
        $deFallbackValue->setString('DE Test Title');

        $result = new MenuUpdateStub();
        $result->setDefaultTitle('Test Title');
        $result->addTitle($deFallbackValue);

        $this->assertEquals($result, $update);
    }

    public function testApplyLocalizedFallbackValueWithExistedFallbackValue()
    {
        $update = new MenuUpdateStub();
        $update->setDefaultTitle('Test Title');

        $this->translator
            ->expects($this->never())
            ->method('trans');

        $this->localizationHelper
            ->expects($this->never())
            ->method('getLocalizations');

        $this->helper->applyLocalizedFallbackValue($update, 'test.title', 'title', 'string');
    }

    public function testApplyLocalizedFallbackValueWithUndefinedName()
    {
        $update = new MenuUpdateStub();

        $this->translator
            ->expects($this->never())
            ->method('trans');

        $this->localizationHelper
            ->expects($this->never())
            ->method('getLocalizations');

        $message = 'Neither the property "undefined_names" nor one of the methods "getUndefinedNames()",';
        $message .= ' "undefinedNames()", "isUndefinedNames()", "hasUndefinedNames()", "__get()" exist and have public';
        $message .= ' access in class "Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub".';

        $this->setExpectedException(NoSuchPropertyException::class, $message);

        $this->helper->applyLocalizedFallbackValue($update, 'test.title', 'undefined_name', 'string');
    }
}
