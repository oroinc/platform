<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityExtendBundle\Tools\EntityGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\ExtendEntityGeneratorExtension;
use Oro\Component\Testing\TempDirExtension;

class EntityGeneratorTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private string $cacheDir;
    private EntityGenerator $entityGenerator;

    protected function setUp(): void
    {
        $this->cacheDir = $this->getTempDir('entity_generator');
        $this->entityGenerator = new EntityGenerator(
            $this->cacheDir,
            [new ExtendEntityGeneratorExtension((new InflectorFactory())->build())]
        );
    }

    public function testGenerateWhenCacheDirIsEmpty(): void
    {
        $schemas = [
            [
                'type'      => 'Extend',
                'entity'    => 'Extend\Entity\Entity1',
                'parent'    => 'Test\Model\Entity1',
                'property'  => ['name' => []],
                'relation'  => [],
                'addremove' => [],
                'default'   => [],
                'doctrine'  => [
                    'Extend\Entity\Entity1' => [
                        'type'   => 'mappedSuperclass',
                        'fields' => [
                            'name' => ['column' => 'name', 'type' => 'string']
                        ]
                    ]
                ]
            ],
            [
                'type'      => 'Custom',
                'entity'    => 'Extend\Entity\Entity2',
                'property'  => ['name' => []],
                'relation'  => [],
                'addremove' => [],
                'default'   => [],
                'doctrine'  => [
                    'Extend\Entity\Entity2' => [
                        'type'   => 'mappedSuperclass',
                        'fields' => [
                            'name' => ['column' => 'name', 'type' => 'string']
                        ]
                    ]
                ]
            ]
        ];

        $this->entityGenerator->generate($schemas);

        self::assertEquals(
            "<?php return array (\n"
            . "  'Extend\\\\Entity\\\\Entity1' => 'Test\\\\Model\\\\Entity1',\n"
            . ');',
            file_get_contents(ExtendClassLoadingUtils::getAliasesPath($this->cacheDir))
        );
        self::assertEquals(
            "<?php\n\n"
            . "namespace Extend\Entity;\n\n"
            . "/** Start: Entity1 */\n"
            . "abstract class Entity1 implements \\Oro\\Bundle\\EntityExtendBundle\\Entity\\ExtendEntityInterface\n"
            . "{\n"
            . "    protected \$name = null;\n\n"
            . "    public function __construct()\n    {\n    }\n\n"
            . "    public function getName()\n    {\n        return \$this->name;\n    }\n\n"
            . "    public function setName(\$value)\n    {\n        \$this->name = \$value; return \$this;\n    }\n"
            . "}\n"
            . "/** End: Entity1 */\n\n"
            . "/** Start: Entity2 */\n"
            . "abstract class Entity2 implements \\Oro\\Bundle\\EntityExtendBundle\\Entity\\ExtendEntityInterface\n"
            . "{\n"
            . "    protected \$id = null;\n"
            . "    protected \$name = null;\n\n"
            . "    public function getId()\n    {\n        return \$this->id;\n    }\n\n"
            . "    public function __toString()\n    {\n        return (string)\$this->getName();\n    }\n\n"
            . "    public function __construct()\n    {\n    }\n\n"
            . "    public function getName()\n    {\n        return \$this->name;\n    }\n\n"
            . "    public function setName(\$value)\n    {\n        \$this->name = \$value; return \$this;\n    }\n"
            . "}\n"
            . "/** End: Entity2 */\n",
            file_get_contents(ExtendClassLoadingUtils::getEntityClassesPath($this->cacheDir))
        );
        self::assertEquals(
            "Extend\Entity\Entity1:\n"
            . "    type: mappedSuperclass\n"
            . "    fields:\n"
            . "        name:\n"
            . "            column: name\n"
            . "            type: string\n",
            file_get_contents(
                ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir) . DIRECTORY_SEPARATOR . 'Entity1.orm.yml'
            )
        );
        self::assertEquals(
            "Extend\Entity\Entity2:\n"
            . "    type: mappedSuperclass\n"
            . "    fields:\n"
            . "        name:\n"
            . "            column: name\n"
            . "            type: string\n",
            file_get_contents(
                ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir) . DIRECTORY_SEPARATOR . 'Entity2.orm.yml'
            )
        );
    }

    /**
     * @depends testGenerateWhenCacheDirIsEmpty
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGenerateWhenCacheDirIsNotEmpty(): void
    {
        $schemas = [
            [
                'type'      => 'Extend',
                'entity'    => 'Extend\Entity\Entity1',
                'parent'    => 'Test\Model\Entity1',
                'property'  => ['name' => []],
                'relation'  => [],
                'addremove' => [],
                'default'   => [],
                'doctrine'  => [
                    'Extend\Entity\Entity1' => [
                        'type'   => 'mappedSuperclass',
                        'fields' => [
                            'name' => ['column' => 'name', 'type' => 'string']
                        ]
                    ]
                ]
            ],
            [
                'type'      => 'Custom',
                'entity'    => 'Extend\Entity\Entity2',
                'property'  => ['name' => []],
                'relation'  => [],
                'addremove' => [],
                'default'   => [],
                'doctrine'  => [
                    'Extend\Entity\Entity2' => [
                        'type'   => 'mappedSuperclass',
                        'fields' => [
                            'name' => ['column' => 'name', 'type' => 'string']
                        ]
                    ]
                ]
            ]
        ];

        // fill cache dir
        $this->entityGenerator->generate($schemas);

        // regenerate caches
        $schemas[1]['type'] = 'Extend';
        $schemas[1]['entity'] = 'Extend\Entity\Entity3';
        $schemas[1]['parent'] = 'Test\Model\Entity3';
        $schemas[1]['doctrine']['Extend\Entity\Entity3'] = $schemas[1]['doctrine']['Extend\Entity\Entity2'];
        unset($schemas[1]['doctrine']['Extend\Entity\Entity2']);
        $this->entityGenerator->generate($schemas);

        self::assertEquals(
            "<?php return array (\n"
            . "  'Extend\\\\Entity\\\\Entity1' => 'Test\\\\Model\\\\Entity1',\n"
            . "  'Extend\\\\Entity\\\\Entity3' => 'Test\\\\Model\\\\Entity3',\n"
            . ');',
            file_get_contents(ExtendClassLoadingUtils::getAliasesPath($this->cacheDir))
        );
        self::assertEquals(
            "<?php\n\n"
            . "namespace Extend\Entity;\n\n"
            . "/** Start: Entity1 */\n"
            . "abstract class Entity1 implements \\Oro\\Bundle\\EntityExtendBundle\\Entity\\ExtendEntityInterface\n"
            . "{\n"
            . "    protected \$name = null;\n\n"
            . "    public function __construct()\n    {\n    }\n\n"
            . "    public function getName()\n    {\n        return \$this->name;\n    }\n\n"
            . "    public function setName(\$value)\n    {\n        \$this->name = \$value; return \$this;\n    }\n"
            . "}\n"
            . "/** End: Entity1 */\n\n"
            . "/** Start: Entity3 */\n"
            . "abstract class Entity3 implements \\Oro\\Bundle\\EntityExtendBundle\\Entity\\ExtendEntityInterface\n"
            . "{\n"
            . "    protected \$name = null;\n\n"
            . "    public function __construct()\n    {\n    }\n\n"
            . "    public function getName()\n    {\n        return \$this->name;\n    }\n\n"
            . "    public function setName(\$value)\n    {\n        \$this->name = \$value; return \$this;\n    }\n"
            . "}\n"
            . "/** End: Entity3 */\n",
            file_get_contents(ExtendClassLoadingUtils::getEntityClassesPath($this->cacheDir))
        );
        self::assertEquals(
            "Extend\Entity\Entity1:\n"
            . "    type: mappedSuperclass\n"
            . "    fields:\n"
            . "        name:\n"
            . "            column: name\n"
            . "            type: string\n",
            file_get_contents(
                ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir) . DIRECTORY_SEPARATOR . 'Entity1.orm.yml'
            )
        );
        self::assertFileDoesNotExist(
            ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir) . DIRECTORY_SEPARATOR . 'Entity2.orm.yml'
        );
        self::assertEquals(
            "Extend\Entity\Entity3:\n"
            . "    type: mappedSuperclass\n"
            . "    fields:\n"
            . "        name:\n"
            . "            column: name\n"
            . "            type: string\n",
            file_get_contents(
                ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir) . DIRECTORY_SEPARATOR . 'Entity3.orm.yml'
            )
        );
    }

    /**
     * @depends testGenerateWhenCacheDirIsEmpty
     */
    public function testGenerateSchemaFilesWhenClassesAreNotGeneratedYet(): void
    {
        ExtendClassLoadingUtils::ensureDirExists(ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir));

        $schema = [
            'type'      => 'Extend',
            'entity'    => 'Extend\Entity\Entity1',
            'parent'    => 'Test\Model\Entity1',
            'property'  => ['name' => []],
            'relation'  => [],
            'addremove' => [],
            'default'   => [],
            'doctrine'  => [
                'Extend\Entity\Entity1' => [
                    'type'   => 'mappedSuperclass',
                    'fields' => [
                        'name' => ['column' => 'name', 'type' => 'string']
                    ]
                ]
            ]
        ];

        $this->entityGenerator->generateSchemaFiles($schema);

        self::assertEquals(
            "<?php\n\n"
            . "namespace Extend\Entity;\n\n"
            . "/** Start: Entity1 */\n"
            . "abstract class Entity1 implements \\Oro\\Bundle\\EntityExtendBundle\\Entity\\ExtendEntityInterface\n"
            . "{\n"
            . "    protected \$name = null;\n\n"
            . "    public function __construct()\n    {\n    }\n\n"
            . "    public function getName()\n    {\n        return \$this->name;\n    }\n\n"
            . "    public function setName(\$value)\n    {\n        \$this->name = \$value; return \$this;\n    }\n"
            . "}\n"
            . "/** End: Entity1 */\n",
            file_get_contents(ExtendClassLoadingUtils::getEntityClassesPath($this->cacheDir))
        );
        self::assertEquals(
            "Extend\Entity\Entity1:\n"
            . "    type: mappedSuperclass\n"
            . "    fields:\n"
            . "        name:\n"
            . "            column: name\n"
            . "            type: string\n",
            file_get_contents(
                ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir) . DIRECTORY_SEPARATOR . 'Entity1.orm.yml'
            )
        );
    }

    /**
     * @depends testGenerateWhenCacheDirIsEmpty
     */
    public function testGenerateSchemaFilesWhenClassesAreAlreadyGenerated(): void
    {
        $schema = [
            'type'      => 'Extend',
            'entity'    => 'Extend\Entity\Entity1',
            'parent'    => 'Test\Model\Entity1',
            'property'  => ['name' => []],
            'relation'  => [],
            'addremove' => [],
            'default'   => [],
            'doctrine'  => [
                'Extend\Entity\Entity1' => [
                    'type'   => 'mappedSuperclass',
                    'fields' => [
                        'name' => ['column' => 'name', 'type' => 'string']
                    ]
                ]
            ]
        ];

        // fill cache dir
        $this->entityGenerator->generate([$schema]);

        // regenerate caches
        $schema['property']['code'] = [];
        $schema['doctrine']['Extend\Entity\Entity1']['fields']['code'] = ['column' => 'name', 'type' => 'string'];
        $this->entityGenerator->generateSchemaFiles($schema);

        self::assertEquals(
            "<?php\n\n"
            . "namespace Extend\Entity;\n\n"
            . "/** Start: Entity1 */\n"
            . "abstract class Entity1 implements \\Oro\\Bundle\\EntityExtendBundle\\Entity\\ExtendEntityInterface\n"
            . "{\n"
            . "    protected \$name = null;\n"
            . "    protected \$code = null;\n\n"
            . "    public function __construct()\n    {\n    }\n\n"
            . "    public function getName()\n    {\n        return \$this->name;\n    }\n\n"
            . "    public function setName(\$value)\n    {\n        \$this->name = \$value; return \$this;\n    }\n\n"
            . "    public function getCode()\n    {\n        return \$this->code;\n    }\n\n"
            . "    public function setCode(\$value)\n    {\n        \$this->code = \$value; return \$this;\n    }\n"
            . "}\n"
            . "/** End: Entity1 */\n",
            file_get_contents(ExtendClassLoadingUtils::getEntityClassesPath($this->cacheDir))
        );
        self::assertEquals(
            "Extend\Entity\Entity1:\n"
            . "    type: mappedSuperclass\n"
            . "    fields:\n"
            . "        name:\n"
            . "            column: name\n"
            . "            type: string\n"
            . "        code:\n"
            . "            column: name\n"
            . "            type: string\n",
            file_get_contents(
                ExtendClassLoadingUtils::getEntityCacheDir($this->cacheDir) . DIRECTORY_SEPARATOR . 'Entity1.orm.yml'
            )
        );
    }
}
