<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\Testing\Unit\EntityTrait;

trait MenuUpdateTrait
{
    use EntityTrait;

    /**
     * @param array  $data
     * @param string $entityClass
     * @return object
     */
    protected function getMenuUpdate($data, $entityClass)
    {
        $titles = $data['titles'];
        unset($data['titles']);

        $descriptions = $data['descriptions'];
        unset($data['descriptions']);

        $scope = $this->getReference($data['scope']);
        unset($data['scope']);

        $entity = $this->getEntity($entityClass, $data);
        $entity->setScope($scope);

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
