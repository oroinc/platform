<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Tools;

use Doctrine\ORM\Query;

use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;

use Oro\Bundle\DataGridBundle\Tools\ChoiceFieldHelper;

class ChoiceFieldHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChoiceFieldHelper */
    protected $choiceHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
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

        $this->setExpectedException('\Exception');
        $this->choiceHelper->guessLabelField($metadata, 'column_name');
    }

    public function testGetChoices()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->getMock();
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

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
            'a1' => 'A1',
            'a2' => 'A2'
        ];
        $this->assertEquals($expected, $this->choiceHelper->getChoices('entity', 'key', 'label'));
    }

    public function testGetTranslatedChoices()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->getMock();
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

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
            'a1' => 'A1_t',
            'a2' => 'A2_t'
        ];
        $this->assertEquals($expected, $this->choiceHelper->getTranslatedChoices('entity', 'key', 'label'));
    }
}
