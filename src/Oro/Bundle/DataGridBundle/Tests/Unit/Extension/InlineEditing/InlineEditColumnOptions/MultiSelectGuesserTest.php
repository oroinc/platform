<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing\InlineEditColumnOption;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\MultiSelectGuesser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Class MultiSelectGuesserTest
 * @package Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing\InlineEditColumnOption
 */
class MultiSelectGuesserTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $choiceHelper;

    /** @var MultiSelectGuesser */
    protected $guesser;

    public function setUp()
    {
        $this->choiceHelper = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Tools\ChoiceFieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->guesser = new MultiSelectGuesser($this->doctrineHelper, $this->choiceHelper);
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

            $this->choiceHelper->expects($this->once())->method('getChoices')
                ->willReturn($expected['choices']);
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
