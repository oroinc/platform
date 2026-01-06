<?php

/**
 * To run this test (from this file's directory):
 *
 * php ../../../../application/commerce-crm-ee/bin/phpunit \
 *     --configuration ../../../../application/commerce-crm-ee/phpunit.xml \
 *     ControlStructureSpacingSniffTest.php
 */

declare(strict_types=1);

namespace Oro\Sniffs\ControlStructures\Tests;

require_once __DIR__ . '/../../../../application/commerce-crm-ee/vendor/squizlabs/php_codesniffer/tests/bootstrap.php';
require_once __DIR__ . '/../Oro/Sniffs/ControlStructures/ControlStructureSpacingSniff.php';

use Oro\Sniffs\ControlStructures\ControlStructureSpacingSniff;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\DummyFile;
use PHP_CodeSniffer\Ruleset;
use PHPUnit\Framework\TestCase;

class OroControlStructureSpacingSniffTest extends TestCase
{
    private ControlStructureSpacingSniff $sniff;

    #[\Override]
    protected function setUp(): void
    {
        $this->sniff = new ControlStructureSpacingSniff();
    }

    public function testRegister(): void
    {
        $tokens = $this->sniff->register();

        self::assertContains(T_IF, $tokens);
        self::assertContains(T_WHILE, $tokens);
        self::assertContains(T_FOREACH, $tokens);
        self::assertContains(T_FOR, $tokens);
        self::assertContains(T_SWITCH, $tokens);
        self::assertContains(T_ELSEIF, $tokens);
        self::assertContains(T_CATCH, $tokens);
        self::assertContains(T_MATCH, $tokens);
    }

    /**
     * @dataProvider validCodeDataProvider
     */
    public function testValidCode(string $code): void
    {
        $file = $this->processCode($code);
        $errors = $file->getErrors();

        self::assertEmpty($errors, 'Expected no errors but found: ' . $this->formatErrors($errors));
    }

    /**
     * @dataProvider invalidCodeDataProvider
     */
    public function testInvalidCode(string $code, string $expectedErrorCode): void
    {
        $file = $this->processCode($code);
        $errors = $file->getErrors();

        self::assertNotEmpty($errors, 'Expected errors but found none');
        self::assertTrue(
            $this->hasErrorCode($errors, $expectedErrorCode),
            "Expected error code '$expectedErrorCode' not found. Found: " . $this->formatErrors($errors)
        );
    }

    public static function validCodeDataProvider(): array
    {
        return [
            'single line if' => [
                '<?php
if ($foo && $bar) {
    echo "test";
}',
            ],
            'multi-line method call ending with ))' => [
                '<?php
if ($this->someMethod(
    $param1,
    $param2
)) {
    echo "test";
}',
            ],
            'multi-line function call ending with ))' => [
                '<?php
if (someFunc(
    $a,
    $b
)) {
    echo "test";
}',
            ],
            'multi-line callable invocation ending with ))' => [
                '<?php
if (($factory)(
    $a,
    $b
)) {
    echo "test";
}',
            ],
            'multi-line array deref call ending with ))' => [
                '<?php
if ($arr["key"](
    $a,
    $b
)) {
    echo "test";
}',
            ],
            'multi-line variable call ending with ))' => [
                '<?php
if ($fn(
    $a,
    $b
)) {
    echo "test";
}',
            ],
            'proper multi-line control structure' => [
                '<?php
if (
    $foo
    && $bar
) {
    echo "test";
}',
            ],
            'while with multi-line method call' => [
                '<?php
while ($this->hasNext(
    $iterator
)) {
    echo "test";
}',
            ],
            'foreach single line' => [
                '<?php
foreach ($items as $item) {
    echo $item;
}',
            ],
            'for single line' => [
                '<?php
for ($i = 0; $i < 10; $i++) {
    echo $i;
}',
            ],
            'switch single line' => [
                '<?php
switch ($value) {
    case 1:
        break;
}',
            ],
            'catch single line' => [
                '<?php
try {
    throw new Exception();
} catch (Exception $e) {
    echo $e->getMessage();
}',
            ],
        ];
    }

    public static function invalidCodeDataProvider(): array
    {
        return [
            'first expression not on line after opening parenthesis' => [
                '<?php
if ($foo
    && $bar
) {
    echo "test";
}',
                'FirstExpressionLine',
            ],
            'grouped boolean expression (not call-like)' => [
                '<?php
if (
    $foo
    && ($bar
        || $baz
    )) {
    echo "test";
}',
                'CloseParenthesisLine',
            ],
            'closing parenthesis not on correct line' => [
                '<?php
if (
    $foo
    && $bar) {
    echo "test";
}',
                'CloseParenthesisLine',
            ],
            'insufficient indentation' => [
                '<?php
if (
$foo
) {
    echo "test";
}',
                'LineIndent',
            ],
            'closing parenthesis wrong indent' => [
                '<?php
if (
    $foo
    && $bar
    ) {
    echo "test";
}',
                'CloseParenthesisIndent',
            ],
            'space after opening parenthesis in single-line condition' => [
                '<?php
if ( $expr) {
    echo "test";
}',
                'SpacingAfterOpenBrace',
            ],
            'space before closing parenthesis in single-line condition' => [
                '<?php
if ($expr ) {
    echo "test";
}',
                'SpaceBeforeCloseBrace',
            ],
            'first expression on same line with multi-line nested call' => [
                '<?php
if (!$this->isGranted(\'DELETE\', $lineItem) &&
    $allowed ||
    !$this->isGranted(
        \'EDIT\',
        $lineItem->getShoppingList()
    )
) {
    break;
}',
                'FirstExpressionLine',
            ],
            'closing parenthesis on same line as nested call closer' => [
                '<?php
if (
    !$this->isGranted(\'DELETE\', $lineItem) &&
    $allowed ||
    !$this->isGranted(
        \'EDIT\',
        $lineItem->getShoppingList()
    )) {
    break;
}',
                'CloseParenthesisLine',
            ],
        ];
    }

    private function processCode(string $code): DummyFile
    {
        $config = new Config(['--standard=PSR12'], false);
        $config->standards = ['PSR12'];

        $ruleset = new Ruleset($config);
        $ruleset->sniffs = [ControlStructureSpacingSniff::class => $this->sniff];
        $ruleset->populateTokenListeners();

        $file = new DummyFile($code, $ruleset, $config);
        $file->process();

        return $file;
    }

    private function hasErrorCode(array $errors, string $errorCode): bool
    {
        foreach ($errors as $lineErrors) {
            foreach ($lineErrors as $columnErrors) {
                foreach ($columnErrors as $error) {
                    if (str_contains($error['source'], $errorCode)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function formatErrors(array $errors): string
    {
        $messages = [];
        foreach ($errors as $line => $lineErrors) {
            foreach ($lineErrors as $column => $columnErrors) {
                foreach ($columnErrors as $error) {
                    $messages[] = sprintf(
                        'Line %d, Column %d: %s (%s)',
                        $line,
                        $column,
                        $error['message'],
                        $error['source']
                    );
                }
            }
        }

        return implode("\n", $messages);
    }
}
