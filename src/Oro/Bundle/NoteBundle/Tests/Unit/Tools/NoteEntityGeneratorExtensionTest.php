<?php


namespace Oro\Bundle\NoteBundle\Tests\Unit\Tools;

use CG\Core\DefaultGeneratorStrategy;

use CG\Generator\PhpClass;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Tools\NoteEntityGeneratorExtension;

class NoteEntityGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var NoteEntityGeneratorExtension */
    protected $extension;

    public function setUp()
    {
        $this->extension = new NoteEntityGeneratorExtension();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports($schemas, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->extension->supports($schemas)
        );
    }

    public function supportsProvider()
    {
        return [
            [
                ['class' => Note::ENTITY_NAME, 'relation' => 'test'],
                true,
            ],
            [
                ['class' => Note::ENTITY_NAME],
                false,
            ],
            [
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
