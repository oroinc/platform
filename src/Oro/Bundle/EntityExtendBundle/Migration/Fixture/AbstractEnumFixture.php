<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Migrations\Data\ORM\LoadLanguageData;
use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * Base enum option fixture.
 */
abstract class AbstractEnumFixture extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var EnumOptionRepository $enumRepo */
        $enumRepo = $manager->getRepository(EnumOption::class);

        $priority = $this->getStartPriority();
        foreach ($this->getData() as $id => $name) {
            if (null !== $enumRepo->getValue(ExtendHelper::buildEnumOptionId($this->getEnumCode(), $id))) {
                continue;
            }
            $isDefault = $id === $this->getDefaultValue();
            $enumOption = $enumRepo->createEnumOption($this->getEnumCode(), $id, $name, $priority++, $isDefault);
            $enumOption->setLocale(Translator::DEFAULT_LOCALE);

            $manager->persist($enumOption);
        }

        $manager->flush();
    }

    protected function getDefaultValue(): ?string
    {
        return null;
    }

    protected function getStartPriority(): int
    {
        return 1;
    }

    abstract protected function getData(): array;

    abstract protected function getEnumCode(): string;

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadLanguageData::class];
    }
}
