<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\EntityExtendBundle\Decorator\OroPropertyAccessorBuilder;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

trait MenuUpdateTrait
{
    protected function getMenuUpdate(array $data, string $entityClass): object
    {
        $titles = $data['titles'];
        unset($data['titles']);

        $descriptions = $data['descriptions'];
        unset($data['descriptions']);

        $scope = $this->getReference($data['scope']);
        unset($data['scope']);

        $entity = new $entityClass();
        $entity->setScope($scope);
        $propertyAccessor = (new OroPropertyAccessorBuilder())->getPropertyAccessor();
        foreach ($data as $name => $val) {
            $propertyAccessor->setValue($entity, $name, $val);
        }

        foreach ($titles as $localization => $title) {
            $fallbackValue = new LocalizedFallbackValue();
            $fallbackValue
                ->setLocalization($this->getReference($localization))
                ->setString($title);

            $entity->addTitle($fallbackValue);
        }

        foreach ($descriptions as $localization => $description) {
            $fallbackValue = new LocalizedFallbackValue();
            $fallbackValue
                ->setLocalization($this->getReference($localization))
                ->setText($description);

            $entity->addDescription($fallbackValue);
        }

        return $entity;
    }
}
