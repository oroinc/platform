<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Form\ChoiceList;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadAddressTypeData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Form\ChoiceList\TranslationChoiceLoader;

class TranslationChoiceLoaderTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadAddressTypeData::class]);
    }

    public function testLoadChoiceListNoQueryBuilder()
    {
        $choiceList = $this->getLoader(null)->loadChoiceList();

        // 2 initially existing entities plus 3 entities from fixtures
        $this->assertCount(5, $choiceList->getChoices());
    }

    public function testLoadChoiceListWithQueryBuilder()
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->andWhere($queryBuilder->expr()->in('a.name', [
            LoadAddressTypeData::TYPE_HOME,
            LoadAddressTypeData::TYPE_WORK
        ]));

        $choiceList = $this->getLoader($queryBuilder)->loadChoiceList();

        $this->assertEquals([
            $this->getReference(LoadAddressTypeData::TYPE_HOME),
            $this->getReference(LoadAddressTypeData::TYPE_WORK)
        ], $choiceList->getChoices());
    }

    public function testLoadChoiceListWithQueryBuilderCallback()
    {
        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder->andWhere($queryBuilder->expr()->in('a.name', [LoadAddressTypeData::TYPE_HOME]));

        $choiceList = $this->getLoader(
            function () use ($queryBuilder) {
                return $queryBuilder;
            }
        )->loadChoiceList();

        $this->assertEquals([$this->getReference(LoadAddressTypeData::TYPE_HOME)], $choiceList->getChoices());
    }

    public function testLoadChoicesForValues()
    {
        $choices = $this->getLoader(null)->loadChoicesForValues(
            [
                LoadAddressTypeData::TYPE_HOME,
                LoadAddressTypeData::TYPE_WORK
            ],
            function (AddressType $addressType) {
                return $addressType->getName();
            }
        );

        $this->assertEquals([
            $this->getReference(LoadAddressTypeData::TYPE_HOME),
            $this->getReference(LoadAddressTypeData::TYPE_WORK)
        ], $choices);
    }

    public function testLoadValuesForChoices()
    {
        $values = $this->getLoader(null)->loadValuesForChoices(
            [
                $this->getReference(LoadAddressTypeData::TYPE_HOME),
                $this->getReference(LoadAddressTypeData::TYPE_WORK)
            ],
            function (AddressType $addressType) {
                return $addressType->getName();
            }
        );

        $this->assertEquals([LoadAddressTypeData::TYPE_HOME, LoadAddressTypeData::TYPE_WORK], $values);
    }

    /**
     * @param null|QueryBuilder|\Closure $queryBuilder
     * @return TranslationChoiceLoader
     */
    private function getLoader($queryBuilder): TranslationChoiceLoader
    {
        return new TranslationChoiceLoader(
            AddressType::class,
            $this->getManagerRegistry(),
            self::getContainer()->get('form.choice_list_factory.default'),
            $queryBuilder
        );
    }

    /**
     * @return QueryBuilder
     */
    private function createQueryBuilder(): QueryBuilder
    {
        return $this->getManagerRegistry()
            ->getManagerForClass(AddressType::class)
            ->getRepository(AddressType::class)
            ->createQueryBuilder('a');
    }

    /**
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private function getManagerRegistry(): Registry
    {
        return self::getContainer()->get('doctrine');
    }
}
