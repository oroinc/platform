<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\InlineEditing\InlineEditColumnOptions;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions\ChoicesGuesser;
use Oro\Bundle\DataGridBundle\Tools\ChoiceFieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ChoicesGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ChoiceFieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $choiceHelper;

    /** @var ChoicesGuesser */
    private $guesser;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->choiceHelper = $this->createMock(ChoiceFieldHelper::class);

        $this->guesser = new ChoicesGuesser($this->doctrineHelper, $this->choiceHelper);
    }

    public function testGuessTranslatableColumnOptions()
    {
        $metadata = new ClassMetadata('\oro\test');
        $metadata->associationMappings = [
            'relationField' => [
                'fieldName' => 'relationField',
                'targetEntity' => '\oro\target',
                'type' => ClassMetadataInfo::MANY_TO_ONE
            ]
        ];

        $targetMetadata = new ClassMetadata('\oro\target');
        $targetMetadata->identifier = ['id'];

        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturnMap([
                ['\oro\test', $metadata],
                ['\oro\target', $targetMetadata],
            ]);

        $this->choiceHelper->expects($this->once())
            ->method('guessLabelField')
            ->willReturn('label');

        $this->choiceHelper->expects($this->once())
            ->method('getChoices')
            ->with('\oro\target', 'id', 'label', null, true)
            ->willReturn(['a1' => 'A1_t', 'a2' => 'A2_t']);

        $result = $this->guesser->guessColumnOptions('relationField', '\oro\test', ['translatable' => true], true);

        $this->assertEquals(
            [
                'inline_editing' => [
                    'enable' => true,
                    'editor' => [
                        'view' => 'oroform/js/app/views/editor/select-editor-view'
                    ]
                ],
                'frontend_type' => 'select',
                'type' => 'field',
                'choices' => [
                    'a1' => 'A1_t',
                    'a2' => 'A2_t'
                ],
                'translatable_options' => false
            ],
            $result
        );
    }

    public function testGuessNonTranslatableColumnOptions()
    {
        $metadata = new ClassMetadata('\oro\test');
        $metadata->associationMappings = [
            'relationField' => [
                'fieldName' => 'relationField',
                'targetEntity' => '\oro\target',
                'type' => ClassMetadataInfo::MANY_TO_ONE
            ]
        ];

        $targetMetadata = new ClassMetadata('\oro\target');
        $targetMetadata->identifier = ['id'];

        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturnMap([
                ['\oro\test', $metadata],
                ['\oro\target', $targetMetadata],
            ]);

        $this->choiceHelper->expects($this->once())
            ->method('guessLabelField')
            ->willReturn('label');

        $this->choiceHelper->expects($this->once())
            ->method('getChoices')
            ->with('\oro\target', 'id', 'label', null, false)
            ->willReturn(['a1' => 'A1', 'a2' => 'A2']);

        $result = $this->guesser->guessColumnOptions('relationField', '\oro\test', ['translatable' => false], true);

        $this->assertEquals(
            [
                'inline_editing' => [
                    'enable' => true,
                    'editor' => [
                        'view' => 'oroform/js/app/views/editor/select-editor-view'
                    ]
                ],
                'frontend_type' => 'select',
                'type' => 'field',
                'choices' => [
                    'a1' => 'A1',
                    'a2' => 'A2'
                ]
            ],
            $result
        );
    }
}
