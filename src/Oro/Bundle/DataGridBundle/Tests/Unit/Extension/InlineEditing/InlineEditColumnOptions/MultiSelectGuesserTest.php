<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing\InlineEditColumnOption;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\MultiSelectGuesser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Class MultiSelectGuesserTest
 * @package Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing\InlineEditColumnOption
 */
class MultiSelectGuesserTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|AclHelper */
    protected $aclHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var MultiSelectGuesser */
    protected $guesser;

    public function setUp()
    {
        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->guesser = new MultiSelectGuesser($this->doctrineHelper, $this->aclHelper);
    }

    public function testRelationGuessHasNotAssociation()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->willReturn(false);

        $metadata->expects($this->never())
            ->method('getAssociationMapping');

        $guessed = $this->guesser->guessColumnOptions('test', 'test', []);

        $this->assertEquals([], $guessed);
    }

    /**
     * @param array $column
     * @param array $expected
     * @param bool $invokes
     *
     * @dataProvider setParametersDataProvider
     */
    public function testRelationGuess($column, $expected, $invokes)
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        if ($invokes) {
            $metadata->expects($this->any())
                ->method('hasAssociation')
                ->willReturn(true);

            $metadata->expects($this->once())
                ->method('getAssociationMapping')
                ->willReturn(['type' => 8]);

            $metadata->expects($this->once())
                ->method('getSingleIdentifierFieldName')
                ->willReturn('key');

            $metadata->expects($this->once())
                ->method('getFieldNames')
                ->willReturn(['test']);

            $metadata->expects($this->once())
                ->method('getTypeOfField')
                ->willReturn('string');

            $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
                ->setMethods(['getResult'])
                ->disableOriginalConstructor()
                ->getMock();
            $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
                ->disableOriginalConstructor()
                ->getMock();

            $this->aclHelper->expects($this->any())
                ->method('apply')
                ->willReturn($qb);
            $em->expects($this->any())
                ->method('getRepository')
                ->willReturn($repo);
            $repo->expects($this->any())
                ->method('createQueryBuilder')
                ->willReturn($qb);
            $qb->expects($this->once())
                ->method('getResult')
                ->willReturn([['key' => 'a1', 'test' => 'A1'], ['key' => 'a2', 'test' => 'A2']]);
        }

        $guessed = $this->guesser->guessColumnOptions('test', 'test', $column);

        $this->assertEquals($expected, $guessed);
    }

    public function setParametersDataProvider()
    {
        return [
            'empty' => [
                [],
                [
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'oroform/js/app/views/editor/multi-checkbox-editor-view'
                        ]
                    ],
                    'frontend_type' => 'multi-select',
                    'type' => 'field',
                    'choices' => [
                        'a1' => 'A1',
                        'a2' => 'A2'
                    ]
                ],
                true
            ],
            'incorrect type fix' => [
                [
                    'frontend_type' => 'multi-select',
                    'type' => 'twig',
                ],
                [
                    'inline_editing' => [
                        'editor' => [
                            'view' => 'oroform/js/app/views/editor/multi-checkbox-editor-view'
                        ]
                    ],
                    'frontend_type' => 'multi-select',
                    'type' => 'field',
                    'choices' => [
                        'a1' => 'A1',
                        'a2' => 'A2'
                    ]
                ],
                true
            ],
            'custom configured' => [
                [
                    'inline_editing' => [
                        'enable' => 1,
                        'editor' => [
                            'view' => 'oroform/js/app/views/editor/multi-select-editor-view-2'
                        ]
                    ],
                    'frontend_type' => 'multi-select',
                ],
                [],
                false
            ],
        ];
    }
}
