<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Tools;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Tools\ChoiceFieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\Walker\TranslatableSqlWalker;

class ChoiceFieldHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ChoiceFieldHelper */
    private $choiceHelper;

    protected function setUp(): void
    {
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->choiceHelper = new ChoiceFieldHelper($this->doctrineHelper, $this->aclHelper);
    }

    public function testGuessLabelFieldHasLabelField()
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('hasField')
            ->with('label')
            ->willReturn(true);
        $metadata->expects($this->never())
            ->method('getFieldNames');

        $this->assertEquals('label', $this->choiceHelper->guessLabelField($metadata, 'column_name'));
    }

    public function testGuessLabelFieldHasStringField()
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('hasField')
            ->willReturn(false);
        $metadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn(['stringField']);
        $metadata->expects($this->once())
            ->method('getTypeOfField')
            ->willReturn('string');

        $this->assertEquals('stringField', $this->choiceHelper->guessLabelField($metadata, 'column_name'));
    }

    public function testGuessLabelFieldExceptionNoStringField()
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('hasField')
            ->willReturn(false);
        $metadata->expects($this->once())
            ->method('getFieldNames')
            ->willReturn([]);

        $this->expectException(\Exception::class);
        $this->choiceHelper->guessLabelField($metadata, 'column_name');
    }

    public function testGetChoices()
    {
        $em = $this->createMock(EntityManager::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with('entity')
            ->willReturn($em);
        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->willReturn($query);
        $query->expects($this->never())
            ->method('setHint');

        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);
        $repo->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([['key' => 'a1', 'label' => 'A1'], ['key' => 'a2', 'label' => 'A2']]);
        $expected = [
            'A1' => 'a1',
            'A2' => 'a2',
        ];
        $this->assertEquals($expected, $this->choiceHelper->getChoices('entity', 'key', 'label'));
    }

    public function testGetTranslatedChoices()
    {
        $em = $this->createMock(EntityManager::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with('entity')
            ->willReturn($em);
        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('setHint')
            ->with(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslatableSqlWalker::class);

        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);
        $repo->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([['key' => 'a1', 'label' => 'A1_t'], ['key' => 'a2', 'label' => 'A2_t']]);
        $expected = [
            'A1_t' => 'a1',
            'A2_t' => 'a2',
        ];
        $this->assertEquals($expected, $this->choiceHelper->getChoices('entity', 'key', 'label', null, true));
    }
}
