<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Tools;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Tools\SchemaDumper;
use Twig\Environment;

class SchemaDumperTest extends \PHPUnit\Framework\TestCase
{
    protected SchemaDumper $schemaDumper;

    protected Schema $schema;

    protected Environment|\PHPUnit\Framework\MockObject\MockObject $twig;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->schema = new Schema();
        $this->schemaDumper = new SchemaDumper($this->twig);

        $this->schemaDumper->acceptSchema($this->schema);
    }

    /**
     * @dataProvider dumpDataProvider
     */
    public function testDump(
        ?array $allowedTables,
        ?string $namespace,
        ?string $expectedNamespace,
        ?string $className,
        ?string $version,
        ?array $extendedOptions
    ): void {
        $this->twig->expects(self::once())
            ->method('render')
            ->with(
                SchemaDumper::SCHEMA_TEMPLATE,
                [
                    'schema' => $this->schema,
                    'allowedTables' => $allowedTables,
                    'namespace' => $expectedNamespace,
                    'className' => $className,
                    'version' => $version,
                    'extendedOptions' => $extendedOptions
                ]
            )
            ->willReturn('TEST');

        self::assertEquals(
            'TEST',
            $this->schemaDumper->dump($allowedTables, $namespace, $className, $version, $extendedOptions)
        );
    }

    public function dumpDataProvider(): array
    {
        return [
            [null, null, null, null, null, null],
            [
                ['test' => true],
                'Acme\DemoBundle\Entity',
                'Acme\DemoBundle',
                'DemoBundleInstaller',
                'v1_1',
                ['test' => ['id' => ['test' => true]]]
            ]
        ];
    }
}
