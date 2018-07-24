<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Attribute\Type;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TranslationBundle\Entity\Language;

abstract class AttributeTypeTestCase extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME = Item::class;
    const FIELD_NAME = 'test_field_name';
    const LOCALE = 'de';

    /** @var FieldConfigModel */
    protected $attribute;

    /** @var Localization */
    protected $localization;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityNameResolver;

    protected function setUp()
    {
        $entity = new EntityConfigModel(self::CLASS_NAME);

        $this->attribute = new FieldConfigModel(self::FIELD_NAME);
        $this->attribute->setEntity($entity);

        $language = new Language();
        $language->setCode(self::LOCALE);

        $this->localization = new Localization();
        $this->localization->setLanguage($language);

        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->entityNameResolver->expects($this->any())
            ->method('getName')
            ->willReturnCallback(
                function ($entity, $format, $locale) {
                    return sprintf(
                        'resolved %s name in %s locale',
                        is_object($entity) ? get_class($entity) : (string)$entity,
                        $locale->getLanguageCode()
                    );
                }
            );
    }

    /**
     * @return AttributeTypeInterface
     */
    abstract protected function getAttributeType();

    /**
     * @dataProvider configurationMethodsDataProvider
     *
     * @param bool $isSearchable
     * @param bool $isFilterable
     * @param bool $isSortable
     */
    public function testConfigurationMethods($isSearchable, $isFilterable, $isSortable)
    {
        $type = $this->getAttributeType();

        $this->assertEquals($isSearchable, $type->isSearchable($this->attribute));
        $this->assertEquals($isFilterable, $type->isFilterable($this->attribute));
        $this->assertEquals($isSortable, $type->isSortable($this->attribute));
    }

    /**
     * @return array|\Generator
     */
    abstract public function configurationMethodsDataProvider();
}
