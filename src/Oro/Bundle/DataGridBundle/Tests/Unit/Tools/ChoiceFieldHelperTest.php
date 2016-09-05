<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Tools;

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

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with('entity')
            ->will($this->returnValue($em));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
                ->setMethods(['getResult'])
                ->disableOriginalConstructor()
                ->getMock();
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->will($this->returnValue($qb));

        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repo));
        $repo->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));
        $qb->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue([['key' => 'a1', 'label' => 'A1'], ['key' => 'a2', 'label' => 'A2']]));
        $expected = [
            'a1' => 'A1',
            'a2' => 'A2'
        ];
        $this->assertEquals($expected, $this->choiceHelper->getChoices('entity', 'key', 'label'));
    }
}
