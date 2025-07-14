<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector\Search;

use Oro\Bundle\ImapBundle\Connector\Search\SearchQuery;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQueryBuilder;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQueryExprItem;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQueryExprOperator;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQueryMatch;
use Oro\Bundle\ImapBundle\Connector\Search\SearchQueryValueBuilder;
use Oro\Bundle\ImapBundle\Connector\Search\SearchStringManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchQueryBuilderTest extends TestCase
{
    /**
     * @dataProvider simpleProvider
     */
    public function testFrom(string $value, int $match): void
    {
        $this->simpleFieldTesting('from', $value, $match);
    }

    public function testFromWithClosure(): void
    {
        $this->simpleFieldTestingWithClosure('from');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testTo(string $value, int $match): void
    {
        $this->simpleFieldTesting('to', $value, $match);
    }

    public function testToWithClosure(): void
    {
        $this->simpleFieldTestingWithClosure('to');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testCc(string $value, int $match): void
    {
        $this->simpleFieldTesting('cc', $value, $match);
    }

    public function testCcWithClosure(): void
    {
        $this->simpleFieldTestingWithClosure('cc');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testBcc(string $value, int $match): void
    {
        $this->simpleFieldTesting('bcc', $value, $match);
    }

    public function testBccWithClosure(): void
    {
        $this->simpleFieldTestingWithClosure('bcc');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testParticipants(string $value, int $match): void
    {
        $this->simpleFieldTesting('participants', $value, $match);
    }

    public function testParticipantsWithClosure(): void
    {
        $this->simpleFieldTestingWithClosure('participants');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testSubject(string $value, int $match): void
    {
        $this->simpleFieldTesting('subject', $value, $match);
    }

    public function testSubjectWithClosure(): void
    {
        $this->simpleFieldTestingWithClosure('subject');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testBody(string $value, int $match): void
    {
        $this->simpleFieldTesting('body', $value, $match);
    }

    public function testBodyWithClosure(): void
    {
        $this->simpleFieldTestingWithClosure('body');
    }

    /**
     * @dataProvider simpleProvider
     */
    public function testAttachment(string $value, int $match): void
    {
        $this->simpleFieldTesting('attachment', $value, $match);
    }

    public function testAttachmentWithClosure(): void
    {
        $this->simpleFieldTestingWithClosure('attachment');
    }

    public function testSent(): void
    {
        $this->rangeFieldTesting('sent');
    }

    public function testReceived(): void
    {
        $this->rangeFieldTesting('received');
    }

    public static function simpleProvider(): array
    {
        return [
            'default match' => ['product', SearchQueryMatch::DEFAULT_MATCH],
            'substring match' => ['product', SearchQueryMatch::SUBSTRING_MATCH],
            'exact match' => ['product', SearchQueryMatch::EXACT_MATCH],
        ];
    }

    private function simpleFieldTesting(string $name, string $value, int $match)
    {
        $expr = $this->createSearchQueryBuilder()->$name($value, $match)->get()->getExpression();

        $expected = [new SearchQueryExprItem($name, $value, $match)];

        $this->assertEquals($expected, $expr->getItems());
    }

    private function simpleFieldTestingWithClosure(string $name)
    {
        $query = $this->createSearchQueryBuilder()
            ->$name(
                function (SearchQueryValueBuilder $builder) {
                    $builder
                        ->value('val1')
                        ->value('val2');
                }
            )
            ->get();
        $expr = $query->getExpression();

        $subQuery = $query->newInstance();
        $subQuery->value('val1');
        $subQuery->andOperator();
        $subQuery->value('val2');

        $expected = [
            new SearchQueryExprItem(
                $name,
                $subQuery->getExpression(),
                SearchQueryMatch::DEFAULT_MATCH
            )
        ];

        $this->assertEquals($expected, $expr->getItems());
    }

    private function rangeFieldTesting(string $name)
    {
        $expr = $this->createSearchQueryBuilder()
            ->$name('val')
            ->get()
            ->getExpression();
        $expected = [new SearchQueryExprItem($name . ':after', 'val', SearchQueryMatch::DEFAULT_MATCH)];
        $this->assertEquals($expected, $expr->getItems());

        $expr = $this->createSearchQueryBuilder()
            ->$name('val', null)
            ->get()
            ->getExpression();
        $expected = [new SearchQueryExprItem($name . ':after', 'val', SearchQueryMatch::DEFAULT_MATCH)];
        $this->assertEquals($expected, $expr->getItems());

        $expr = $this->createSearchQueryBuilder()
            ->$name(null, 'val')
            ->get()
            ->getExpression();
        $expected = [new SearchQueryExprItem($name . ':before', 'val', SearchQueryMatch::DEFAULT_MATCH)];
        $this->assertEquals($expected, $expr->getItems());

        $expr = $this->createSearchQueryBuilder()
            ->$name('val1', 'val2')
            ->get()
            ->getExpression();
        $expected = [
            new SearchQueryExprItem($name . ':after', 'val1', SearchQueryMatch::DEFAULT_MATCH),
            new SearchQueryExprOperator('AND'),
            new SearchQueryExprItem($name . ':before', 'val2', SearchQueryMatch::DEFAULT_MATCH)
        ];
        $this->assertEquals($expected, $expr->getItems());
    }

    private function createSearchQueryBuilder(): SearchQueryBuilder
    {
        $searchStringManager = $this->createMock(SearchStringManagerInterface::class);
        $searchStringManager->expects($this->any())
            ->method('isAcceptableItem')
            ->willReturn(true);
        $searchStringManager->expects($this->never())
            ->method('buildSearchString');

        return new SearchQueryBuilder(new SearchQuery($searchStringManager));
    }
}
