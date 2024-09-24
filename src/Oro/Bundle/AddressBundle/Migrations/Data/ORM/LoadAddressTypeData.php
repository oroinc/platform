<?php

namespace Oro\Bundle\AddressBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;

/**
 * Loads address types.
 */
class LoadAddressTypeData extends AbstractTranslatableEntityFixture
{
    private const TRANSLATION_PREFIX = 'address_type';

    #[\Override]
    protected function loadEntities(ObjectManager $manager): void
    {
        $addressTypeRepository = $manager->getRepository(AddressType::class);
        $translationLocales = $this->getTranslationLocales();
        $addressTypes = [
            AddressType::TYPE_BILLING,
            AddressType::TYPE_SHIPPING
        ];
        foreach ($translationLocales as $locale) {
            foreach ($addressTypes as $addressName) {
                /** @var AddressType $addressType */
                $addressType = $addressTypeRepository->findOneBy(['name' => $addressName]);
                if (!$addressType) {
                    $addressType = new AddressType($addressName);
                }

                $addressType->setLocale($locale);
                $addressType->setLabel($this->translate($addressName, self::TRANSLATION_PREFIX, $locale));
                $manager->persist($addressType);
            }
            $manager->flush();
        }
    }
}
