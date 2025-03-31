<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\EntityExtend;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\EntityExtend\Extension\EntityExtendTransportTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\EntityExtend\LocaleEntityFieldExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestExtendedEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class LocaleEntityFieldExtensionTest extends WebTestCase
{
    use EntityExtendTransportTrait;

    private LocaleEntityFieldExtension $localeEntityFieldExtension;

    public function setUp(): void
    {
        self::bootKernel();

        $storage = self::getContainer()->get('oro_locale.storage.entity_fallback_fields_storage');
        $namesProvider = self::getContainer()->get('oro_locale.provider.default_fallback_methods_names');
        $this->localeEntityFieldExtension = new LocaleEntityFieldExtension($storage, $namesProvider);
    }

    public function testIssetForNotLocalizedEntity(): void
    {
        $transport = $this->createTransport(TestExtendedEntity::class);
        $transport->setName('default_name');

        $this->localeEntityFieldExtension->isset($transport);
        self::assertFalse($transport->isProcessed());
    }

    /**
     * @dataProvider localizedPropertiesDataProvider
     */
    public function testIsset(
        string $class,
        string $name,
        bool $isProcessed,
        mixed $result
    ) {
        $transport = $this->createTransport($class);
        $transport->setName($name);

        $this->localeEntityFieldExtension->isset($transport);
        self::assertSame($isProcessed, $transport->isProcessed());
        self::assertSame($result, $transport->getResult());
    }

    public function localizedPropertiesDataProvider(): array
    {
        return [
            'titles localized property exists' => [
                'class' => Localization::class,
                'name' => 'titles',
                'isProcessed' => true,
                'result' => true
            ],
            'title localized property exists' => [
                'class' => Localization::class,
                'name' => 'title',
                'isProcessed' => true,
                'result' => true
            ],
            'defaultTitle localized property exists' => [
                'class' => Localization::class,
                'name' => 'defaultTitle',
                'isProcessed' => true,
                'result' => true
            ],
            'default_title localized property exists' => [
                'class' => Localization::class,
                'name' => 'default_title',
                'isProcessed' => true,
                'result' => true
            ],
            'undefined localized property exists' => [
                'class' => Localization::class,
                'name' => 'undefined',
                'isProcessed' => false,
                'result' => null
            ],
        ];
    }

    public function testPropertyExistsNotLocalized(): void
    {
        $transport = $this->createTransport(TestExtendedEntity::class);
        $transport->setName('default_name');

        $this->localeEntityFieldExtension->propertyExists($transport);
        self::assertFalse($transport->isProcessed());
    }

    /**
     * @dataProvider localizedPropertiesDataProvider
     */
    public function testPropertyExists(
        string $class,
        string $name,
        bool $isProcessed,
        mixed $result
    ): void {
        $transport = $this->createTransport($class);
        $transport->setName($name);

        $this->localeEntityFieldExtension->propertyExists($transport);
        self::assertSame($isProcessed, $transport->isProcessed());
        self::assertSame($result, $transport->getResult());
    }

    public function testMethodExists(): void
    {
        // not localized entity
        $transport = $this->createTransport(TestExtendedEntity::class);
        $transport->setName('getDefaultName');

        $this->localeEntityFieldExtension->methodExists($transport);
        self::assertFalse($transport->isProcessed());

        // get method exists
        $transport = $this->createTransport(Localization::class);
        $transport->setName('getDefaultTitle');

        $this->localeEntityFieldExtension->methodExists($transport);
        self::assertTrue($transport->isProcessed());

        // set method exists
        $transport = $this->createTransport(Localization::class);
        $transport->setName('setDefaultTitle');

        $this->localeEntityFieldExtension->methodExists($transport);
        self::assertTrue($transport->isProcessed());

        // default method exists
        $transport = $this->createTransport(Localization::class);
        $transport->setName('defaultTitle');

        $this->localeEntityFieldExtension->methodExists($transport);
        self::assertTrue($transport->isProcessed());
    }

    public function testSetUndefinedProperty(): void
    {
        $transport = $this->createTransport(TestExtendedEntity::class);
        $transport->setName('default_name');
        $transport->setValue('TestVal1');

        $this->localeEntityFieldExtension->set($transport);
        self::assertFalse($transport->isProcessed());
        self::assertNull($transport->getResult());
    }

    public function testSetApplicable(): void
    {
        $transport = $this->createTransport(new Localization());
        $transport->setName('default_title');
        $transport->setValue('TestVal1');

        $this->localeEntityFieldExtension->set($transport);
        self::assertTrue(true, $transport->isProcessed());

        $localizedTitles = $transport->getStorage()->offsetGet('titles');
        self::assertInstanceOf(ArrayCollection::class, $localizedTitles);
        self::assertInstanceOf(LocalizedFallbackValue::class, $localizedTitles->first());
        self::assertSame('TestVal1', $localizedTitles->first()->string);
    }

    public function testGetUndefinedProperty(): void
    {
        $transport = $this->createTransport(TestExtendedEntity::class);
        $transport->setName('default_name');
        $transport->setValue('TestVal1');

        $this->localeEntityFieldExtension->get($transport);
        self::assertFalse($transport->isProcessed());
        self::assertNull($transport->getResult());
    }

    public function testGetApplicable(): void
    {
        $transport = $this->createTransport(new Localization());
        $transport->setName('default_title');

        $this->localeEntityFieldExtension->get($transport);
        self::assertTrue($transport->isProcessed());

        $localizedTitles = $transport->getStorage()->offsetGet('titles');
        self::assertInstanceOf(ArrayCollection::class, $localizedTitles);
        self::assertTrue($localizedTitles->isEmpty());

        // set before get
        $transport = $this->createTransport(new Localization());
        $transport->setName('default_title');
        $transport->setValue('TestVal1');
        $this->localeEntityFieldExtension->set($transport);

        $this->localeEntityFieldExtension->get($transport);
        self::assertTrue($transport->isProcessed());

        $localizedTitles = $transport->getStorage()->offsetGet('titles');
        self::assertInstanceOf(ArrayCollection::class, $localizedTitles);
        self::assertFalse($localizedTitles->isEmpty());

        self::assertInstanceOf(LocalizedFallbackValue::class, $localizedTitles->first());
        self::assertSame('TestVal1', $localizedTitles->first()->string);
    }
}
