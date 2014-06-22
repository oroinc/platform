<?php


namespace Oro\Bundle\NoteBundle\Tests\Unit\Tools;

use CG\Core\DefaultGeneratorStrategy;

use CG\Generator\PhpClass;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityGenerator;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Tools\NoteExtendEntityGeneratorExtension;

class NoteGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var NoteExtendEntityGeneratorExtension */
    protected $extension;

    public function setUp()
    {
        $this->extension = new NoteExtendEntityGeneratorExtension();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports($actionType, $schemas, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->extension->supports($actionType, $schemas)
        );
    }

    public function supportsProvider()
    {
        return [
            [
                ExtendEntityGenerator::ACTION_GENERATE,
                ['class' => Note::ENTITY_NAME, 'relation' => 'test'],
                true,
            ],
            [
                ExtendEntityGenerator::ACTION_PRE_PROCESS,
                ['class' => Note::ENTITY_NAME, 'relation' => 'test'],
                false,
            ],
            [
                ExtendEntityGenerator::ACTION_GENERATE,
                ['class' => Note::ENTITY_NAME],
                false,
            ],
            [
                ExtendEntityGenerator::ACTION_GENERATE,
                ['class' => 'Test\Entity', 'relation' => 'test'],
                false,
            ],
        ];
    }

    public function testGenerate()
    {
        $schema = [
            'relationData' => [
                [
                    'field_id'      => new FieldConfigId('extend', 'Test\Entity', 'targetField1'),
                    'target_entity' => 'Test\TargetEntity1',
                ],
                [
                    'field_id'      => new FieldConfigId('extend', 'Test\Entity', 'targetField2'),
                    'target_entity' => 'Test\TargetEntity2',
                ],
            ],
        ];

        $class = PhpClass::create('Test\Entity');

        $this->extension->generate($schema, $class);
        $strategy     = new DefaultGeneratorStrategy();
        $classBody    = $strategy->generate($class);
        $expectedBody = file_get_contents(__DIR__ . '/Fixtures/generationResult.txt');

        $this->assertEquals(trim($expectedBody), $classBody);
    }
}
