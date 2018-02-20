<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Component\Testing\Unit\EntityTrait;

trait FallbackTrait
{
    use EntityTrait;

    /**
     * @param object $object
     * @param string $method
     */
    protected function assertFallbackValue($object, $method)
    {
        $localization1 = $this->getEntity(Localization::class, ['id' => 1]);
        $localization2 = $this->getEntity(Localization::class, ['id' => 2, 'parentLocalization' => $localization1]);
        $localization3 = $this->getEntity(Localization::class, ['id' => 3, 'parentLocalization' => $localization2]);

        $value1 = $this->getEntity(LocalizedFallbackValue::class, [
            'string' => 'value1',
            'fallback' => FallbackType::NONE,
            'localization' => null,
        ]);

        $value2 = $this->getEntity(LocalizedFallbackValue::class, [
            'string' => 'value2',
            'fallback' => FallbackType::NONE,
            'localization' => $localization1,
        ]);

        $value3 = $this->getEntity(LocalizedFallbackValue::class, [
            'string' => 'value3',
            'fallback' => FallbackType::PARENT_LOCALIZATION,
            'localization' => $localization2,
        ]);

        $value4 = $this->getEntity(LocalizedFallbackValue::class, [
            'string' => 'value4',
            'fallback' => FallbackType::SYSTEM,
            'localization' => $localization3,
        ]);

        $values = new ArrayCollection([$value1, $value2, $value3, $value4]);

        // test 'FallbackType::NONE'
        $this->assertEquals($value2, $object->$method($values, $localization1));
        // test 'FallbackType::PARENT_LOCALIZATION;
        $this->assertEquals($value2, $object->$method($values, $localization2));
        // test 'FallbackType::SYSTEM;
        $this->assertEquals($value1, $object->$method($values, $localization3));

        // test logic exception
        $badValues = new ArrayCollection([$value1, $value1]);
        $this->expectException('LogicException');
        $object->$method($badValues, $localization1);
    }
}
