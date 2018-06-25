<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Tools;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Oro\Bundle\DataGridBundle\Tools\ChoiceFieldHelper;

class ChoiceFieldHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChoiceFieldHelper */
    protected $choiceHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $aclHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->choiceHelper = new ChoiceFieldHelper($this->doctrineHelper, $this->aclHelper);
    }

    public function testGuessLabelFieldHasLabelField()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
                ->method('hasField')
                ->with('label')
                ->will($this->returnValue(true));
        $metadata->expects($this->never())->method('getFieldNames');

        $this->assertEquals('label', $this->choiceHelper->guessLabelField($metadata, 'column_name'));
    }

    public function testGuessLabelFieldHasStringField()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
                ->method('hasField')
                ->will($this->returnValue(false));
        $metadata->expects($this->once())
                ->method('getFieldNames')
                ->will($this->returnValue(['stringField']));
        $metadata->expects($this->once())
            ->method('getTypeOfField')
            ->will($this->returnValue('string'));

        $this->assertEquals('stringField', $this->choiceHelper->guessLabelField($metadata, 'column_name'));
    }

    public function testGuessLabelFieldExceptionNoStringField()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
                ->method('hasField')
                ->will($this->returnValue(false));
        $metadata->expects($this->once())
                ->method('getFieldNames')
                ->will($this->returnValue([]));

        $this->expectException('\Exception');
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
            ->will($this->returnValue($em));
        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->will($this->returnValue($query));
        $query->expects($this->never())
            ->method('setHint');

        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repo));
        $repo->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue([['key' => 'a1', 'label' => 'A1'], ['key' => 'a2', 'label' => 'A2']]));
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
            ->will($this->returnValue($em));
        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('setHint')
            ->with(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);

        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repo));
        $repo->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue([['key' => 'a1', 'label' => 'A1_t'], ['key' => 'a2', 'label' => 'A2_t']]));
        $expected = [
            'A1_t' => 'a1',
            'A2_t' => 'a2',
        ];
        $this->assertEquals($expected, $this->choiceHelper->getChoices('entity', 'key', 'label', null, true));
    }
}
