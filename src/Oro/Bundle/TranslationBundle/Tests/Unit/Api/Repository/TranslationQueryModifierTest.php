<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Api\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\TranslationBundle\Api\Repository\TranslationQueryModifier;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TranslationQueryModifierTest extends OrmTestCase
{
    private EntityManagerInterface $em;
    private TranslationQueryModifier $queryModifier;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AttributeDriver([]));

        $this->queryModifier = new TranslationQueryModifier();
    }

    private function getTranslationQueryBuilder(bool $withTranslationJoin): QueryBuilder
    {
        $qb = $this->em->createQueryBuilder()
            ->from(TranslationKey::class, 'e')
            ->select('e')
            ->innerJoin(Language::class, 'language', Join::WITH, '1 = 1');
        if ($withTranslationJoin) {
            $qb->leftJoin(
                Translation::class,
                'translation',
                Join::WITH,
                'translation.translationKey = e AND translation.language = language'
            );
        }

        return $qb;
    }

    private function getEntityConfig(): EntityConfig
    {
        $config = new EntityConfig();
        $config->addField('languageCode')->setExcluded(true);
        $config->addField('translationId')->setExcluded(true);
        $config->addField('translatedValue')->setExcluded(true);

        return $config;
    }

    public function testWhenNoComputedFields(): void
    {
        $qb = $this->getTranslationQueryBuilder(false);
        $config = $this->getEntityConfig();

        $this->queryModifier->updateQuery($qb, $config);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\TranslationBundle\Entity\TranslationKey e'
            . ' INNER JOIN Oro\Bundle\TranslationBundle\Entity\Language language WITH 1 = 1',
            $qb->getDQL()
        );
    }

    public function testWhenNoComputedFieldsAndFilterById(): void
    {
        $qb = $this->getTranslationQueryBuilder(false);
        $qb->andWhere(
            $qb->expr()->andX($qb->expr()->eq('e.id', ':id'), $qb->expr()->eq('language.code', ':id_lang'))
        );
        $qb->setParameter('id', 1)->setParameter('id_lang', 'en_US');
        $config = $this->getEntityConfig();

        $this->queryModifier->updateQuery($qb, $config);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\TranslationBundle\Entity\TranslationKey e'
            . ' WHERE e.id = :id',
            $qb->getDQL()
        );
        self::assertCount(1, $qb->getParameters());
        self::assertNotNull($qb->getParameter('id'));
        self::assertNull($qb->getParameter('id_lang'));
    }

    public function testWhenNoComputedFieldsAndFilterBySeveralIds(): void
    {
        $qb = $this->getTranslationQueryBuilder(false);
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->andX($qb->expr()->eq('e.id', ':id1'), $qb->expr()->eq('language.code', ':id1_lang')),
                $qb->expr()->andX($qb->expr()->eq('e.id', ':id2'), $qb->expr()->eq('language.code', ':id2_lang'))
            )
        );
        $qb->setParameter('id1', 1)->setParameter('id1_lang', 'en_US');
        $qb->setParameter('id2', 2)->setParameter('id2_lang', 'en_US');
        $config = $this->getEntityConfig();

        $this->queryModifier->updateQuery($qb, $config);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\TranslationBundle\Entity\TranslationKey e'
            . ' INNER JOIN Oro\Bundle\TranslationBundle\Entity\Language language WITH 1 = 1'
            . ' WHERE (e.id = :id1 AND language.code = :id1_lang) OR (e.id = :id2 AND language.code = :id2_lang)',
            $qb->getDQL()
        );
        self::assertCount(4, $qb->getParameters());
    }

    public function testWhenNoComputedFieldsAndFilterByOneLanguage(): void
    {
        $qb = $this->getTranslationQueryBuilder(false);
        $qb->andWhere(new Comparison('language.code', Comparison::EQ, ':languageCode'))
            ->setParameter('languageCode', 'en_US');
        $config = $this->getEntityConfig();

        $this->queryModifier->updateQuery($qb, $config);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\TranslationBundle\Entity\TranslationKey e',
            $qb->getDQL()
        );
        self::assertCount(0, $qb->getParameters());
    }

    public function testWhenNoComputedFieldsAndFilterByOneLanguageButInvalidLanguageCode(): void
    {
        $qb = $this->getTranslationQueryBuilder(false);
        $qb->andWhere(new Comparison('language.code', Comparison::EQ, ':languageCode'))
            ->setParameter('languageCode', '!invalid');
        $config = $this->getEntityConfig();

        $this->queryModifier->updateQuery($qb, $config);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\TranslationBundle\Entity\TranslationKey e'
            . ' INNER JOIN Oro\Bundle\TranslationBundle\Entity\Language language WITH 1 = 1'
            . ' WHERE language.code = :languageCode',
            $qb->getDQL()
        );
        self::assertCount(1, $qb->getParameters());
        self::assertNotNull($qb->getParameter('languageCode'));
    }

    public function testWhenNoComputedFieldsAndFilterBySeveralLanguages(): void
    {
        $qb = $this->getTranslationQueryBuilder(false);
        $qb->andWhere(new Func('language.code IN ', ':languageCode'))
            ->setParameter('languageCode', ['en_US', 'en']);
        $config = $this->getEntityConfig();

        $this->queryModifier->updateQuery($qb, $config);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\TranslationBundle\Entity\TranslationKey e'
            . ' INNER JOIN Oro\Bundle\TranslationBundle\Entity\Language language WITH 1 = 1'
            . ' WHERE language.code IN (:languageCode)',
            $qb->getDQL()
        );
        self::assertCount(1, $qb->getParameters());
        self::assertNotNull($qb->getParameter('languageCode'));
    }

    public function testWithComputedFields(): void
    {
        $qb = $this->getTranslationQueryBuilder(true);
        $config = $this->getEntityConfig();
        $config->getField('languageCode')->setExcluded(false);
        $config->getField('translationId')->setExcluded(false);
        $config->getField('translatedValue')->setExcluded(false);

        $this->queryModifier->updateQuery($qb, $config);

        self::assertEquals(
            'SELECT e,'
            . ' language.code AS languageCode,'
            . ' translation.id AS translationId,'
            . ' translation.value AS translatedValue,'
            . ' (CASE WHEN translation.id IS NULL THEN false ELSE true END) AS hasTranslation'
            . ' FROM Oro\Bundle\TranslationBundle\Entity\TranslationKey e'
            . ' INNER JOIN Oro\Bundle\TranslationBundle\Entity\Language language WITH 1 = 1'
            . ' LEFT JOIN Oro\Bundle\TranslationBundle\Entity\Translation translation'
            . ' WITH translation.translationKey = e AND translation.language = language',
            $qb->getDQL()
        );
    }

    public function testWithComputedFieldAndOrderByLanguageCodeAndFilterByOneLanguage(): void
    {
        $qb = $this->getTranslationQueryBuilder(false);
        $qb->andWhere(new Comparison('language.code', Comparison::EQ, ':languageCode'))
            ->setParameter('languageCode', 'en_US');
        $qb->orderBy('e.key');
        $qb->addOrderBy('e.id', 'DESC');
        $qb->addOrderBy('language.code');
        $qb->addOrderBy('e.domain');
        $config = $this->getEntityConfig();
        $config->getField('languageCode')->setExcluded(false);

        $this->queryModifier->updateQuery($qb, $config);

        self::assertEquals(
            'SELECT e,'
            . ' \'en_US\' AS languageCode'
            . ' FROM Oro\Bundle\TranslationBundle\Entity\TranslationKey e'
            . ' ORDER BY e.key ASC, e.id DESC, e.domain ASC',
            $qb->getDQL()
        );
        self::assertCount(0, $qb->getParameters());
    }

    public function testWithComputedFieldAndOrderByLanguageCodeAndFilterByOneLanguageAndTranslationJoin(): void
    {
        $qb = $this->getTranslationQueryBuilder(true);
        $qb->andWhere(new Comparison('language.code', Comparison::EQ, ':languageCode'))
            ->setParameter('languageCode', 'en_US');
        $qb->orderBy('e.key');
        $qb->addOrderBy('e.id', 'DESC');
        $qb->addOrderBy('language.code');
        $qb->addOrderBy('e.domain');
        $config = $this->getEntityConfig();
        $config->getField('languageCode')->setExcluded(false);

        $this->queryModifier->updateQuery($qb, $config);

        self::assertEquals(
            'SELECT e,'
            . ' \'en_US\' AS languageCode,'
            . ' (CASE WHEN translation.id IS NULL THEN false ELSE true END) AS hasTranslation'
            . ' FROM Oro\Bundle\TranslationBundle\Entity\TranslationKey e'
            . ' INNER JOIN Oro\Bundle\TranslationBundle\Entity\Language language WITH language.code = :languageCode'
            . ' LEFT JOIN Oro\Bundle\TranslationBundle\Entity\Translation translation'
            . ' WITH translation.translationKey = e AND translation.language = language'
            . ' ORDER BY e.key ASC, e.id DESC, e.domain ASC',
            $qb->getDQL()
        );
        self::assertCount(1, $qb->getParameters());
        self::assertNotNull($qb->getParameter('languageCode'));
    }

    public function testWithOrderById(): void
    {
        $qb = $this->getTranslationQueryBuilder(false);
        $qb->orderBy('e.key');
        $qb->addOrderBy('e.id', 'DESC');
        $qb->addOrderBy('e.domain');
        $config = $this->getEntityConfig();

        $this->queryModifier->updateQuery($qb, $config);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\TranslationBundle\Entity\TranslationKey e'
            . ' INNER JOIN Oro\Bundle\TranslationBundle\Entity\Language language WITH 1 = 1'
            . ' ORDER BY e.key ASC, e.id DESC, language.code DESC, e.domain ASC',
            $qb->getDQL()
        );
    }

    public function testWhenFilterByOneLanguageAndSomeOtherFilter(): void
    {
        $qb = $this->getTranslationQueryBuilder(false);
        $qb->andWhere(new Comparison('language.code', Comparison::EQ, ':languageCode'))
            ->andWhere(new Comparison('e.key', Comparison::EQ, ':key'))
            ->setParameter('languageCode', 'en_US')
            ->setParameter('key', 'test');
        $config = $this->getEntityConfig();

        $this->queryModifier->updateQuery($qb, $config);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\TranslationBundle\Entity\TranslationKey e'
            . ' WHERE e.key = :key',
            $qb->getDQL()
        );
        self::assertCount(1, $qb->getParameters());
        self::assertNull($qb->getParameter('languageCode'));
        self::assertNotNull($qb->getParameter('key'));
    }

    public function testWhenFilterByOneLanguageWithNotEqualsOperator(): void
    {
        $qb = $this->getTranslationQueryBuilder(false);
        $qb->andWhere(new Comparison('language.code', Comparison::NEQ, ':languageCode'))
            ->setParameter('languageCode', 'en_US');
        $config = $this->getEntityConfig();

        $this->queryModifier->updateQuery($qb, $config);

        self::assertEquals(
            'SELECT e'
            . ' FROM Oro\Bundle\TranslationBundle\Entity\TranslationKey e'
            . ' INNER JOIN Oro\Bundle\TranslationBundle\Entity\Language language WITH 1 = 1'
            . ' WHERE language.code <> :languageCode',
            $qb->getDQL()
        );
        self::assertCount(1, $qb->getParameters());
        self::assertNotNull($qb->getParameter('languageCode'));
    }

    public function testWhenRightPartOfLanguageCodeFilterIsNotParameter(): void
    {
        $qb = $this->getTranslationQueryBuilder(true);
        $qb->leftJoin('translation.language', 'tl');
        $qb->andWhere(new Comparison('language.code', Comparison::EQ, 'tl.code'));
        $config = $this->getEntityConfig();

        $this->queryModifier->updateQuery($qb, $config);

        self::assertEquals(
            'SELECT e,'
            . ' (CASE WHEN translation.id IS NULL THEN false ELSE true END) AS hasTranslation'
            . ' FROM Oro\Bundle\TranslationBundle\Entity\TranslationKey e'
            . ' INNER JOIN Oro\Bundle\TranslationBundle\Entity\Language language WITH 1 = 1'
            . ' LEFT JOIN Oro\Bundle\TranslationBundle\Entity\Translation translation'
            . ' WITH translation.translationKey = e AND translation.language = language'
            . ' LEFT JOIN translation.language tl'
            . ' WHERE language.code = tl.code',
            $qb->getDQL()
        );
        self::assertCount(0, $qb->getParameters());
    }
}
