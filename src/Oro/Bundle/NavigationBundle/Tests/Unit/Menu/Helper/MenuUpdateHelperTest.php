<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Helper;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\NavigationBundle\Menu\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\Translation\TranslatorInterface;

class MenuUpdateHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $localizationHelper;

    /** @var MenuUpdateHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->helper = new MenuUpdateHelper($this->translator, $this->localizationHelper);
    }

    public function testApplyLocalizedFallbackValue()
    {
        $update = new MenuUpdateStub();

        $this->translator
            ->expects($this->exactly(3))
            ->method('trans')
            ->will($this->returnValueMap([
                ['test.title', [], null, 'en', 'EN Test Title'],
                ['test.title', [], null, 'de', 'DE Test Title'],
            ]));

        $enLocalization = new Localization();
        $enLocalization->setLanguage($this->getEntity(Language::class, ['code' => 'en']));

        $deLocalization = new Localization();
        $deLocalization->setLanguage($this->getEntity(Language::class, ['code' => 'de']));

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

        $enFallbackValue = new LocalizedFallbackValue();
        $enFallbackValue->setLocalization($enLocalization);
        $enFallbackValue->setFallback(FallbackType::SYSTEM);

        $result = new MenuUpdateStub();
        $result->setDefaultTitle('EN Test Title');
        $result->addTitle($enFallbackValue);
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

        $this->expectException(NoSuchPropertyException::class);
        $this->expectExceptionMessage($message);

        $this->helper->applyLocalizedFallbackValue($update, 'test.title', 'undefined_name', 'string');
    }
}
