<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\NameStrategy;
use Oro\Bundle\DataGridBundle\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class NameStrategyTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack */
    private $requestStack;

    /** @var NameStrategy */
    private $nameStrategy;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();

        $this->nameStrategy = new NameStrategy($this->requestStack);
    }

    /**
     * @dataProvider validGridNamesDataProvider
     */
    public function testParseGridNameWorks(string $name, string $expectedGridName)
    {
        $this->assertEquals($expectedGridName, $this->nameStrategy->parseGridName($name));
    }

    /**
     * @dataProvider validGridNamesDataProvider
     */
    public function testParseGridScopeWorks(string $name, string $expectedGridName, string $expectedGridScope)
    {
        $this->assertEquals($expectedGridScope, $this->nameStrategy->parseGridScope($name));
    }

    /**
     * @dataProvider validGridNamesDataProvider
     */
    public function testBuildGridFullNameWorks(string $expectedFullName, string $gridName, string $gridScope)
    {
        $this->assertEquals(
            $gridScope ? $expectedFullName : $gridName,
            $this->nameStrategy->buildGridFullName($gridName, $gridScope)
        );
    }

    public function validGridNamesDataProvider(): array
    {
        return [
            [
                'test_grid:test_scope',
                'test_grid',
                'test_scope',
            ],
            [
                'test_grid',
                'test_grid',
                '',
            ],
            [
                'test_grid:',
                'test_grid',
                '',
            ],
        ];
    }

    /**
     * @dataProvider invalidGridNamesDataProvider
     */
    public function testParseGridNameFails(string $expectedMessage, string $name)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->nameStrategy->parseGridName($name);
    }

    /**
     * @dataProvider invalidGridNamesDataProvider
     */
    public function testParseGridScopeFails(string $expectedMessage, string $name)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->nameStrategy->parseGridScope($name);
    }

    /**
     * @dataProvider invalidGridNamesDataProvider
     */
    public function testBuildGridFullNameFails(
        string $expectedMessage,
        string $name,
        string $gridName,
        string $gridScope
    ) {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->nameStrategy->buildGridFullName($gridName, $gridScope);
    }

    public function invalidGridNamesDataProvider(): array
    {
        return [
            'too many delimiters' => [
                'Grid name "test_grid:test_scope:test_scope" is invalid, '
                . 'it should not contain more than one occurrence of ":".',
                'test_grid:test_scope:test_scope',
                'test_grid',
                'test_scope:test_scope',
            ],
            'empty name' => [
                'Grid name ":test_scope" is invalid, name must be not empty.',
                ':test_scope',
                '',
                'test_scope',
            ],
        ];
    }

    public function testGetGridUniqueNameShouldReturnOriginalNameIfRequestIsNull()
    {
        $name = 'name';

        $uniqueName = $this->nameStrategy->getGridUniqueName($name);
        $this->assertEquals($name, $uniqueName);
    }

    public function testGetGridUniqueNameShouldReturnOriginalNameIfCurrentRequestIsNotRelatedWithWidget()
    {
        $name = 'name';
        $request = new Request();
        $this->requestStack->push($request);

        $uniqueName = $this->nameStrategy->getGridUniqueName($name);
        $this->assertEquals($name, $uniqueName);
    }

    public function testGetGridShouldReturnNameSuffixedWithWidgetIdIfCurrentRequestIsRelatedWithWidget()
    {
        $request = new Request([
            '_widgetId' => 5
        ]);
        $this->requestStack->push($request);

        $uniqueName = $this->nameStrategy->getGridUniqueName('name');
        $this->assertEquals('name_w5', $uniqueName);
    }

    public function testGetGridShouldReturnNameFromQueryStringIfCurrentRequestContainsIt()
    {
        $request = new Request([
            'name_w1' => 'test'
        ]);
        $this->requestStack->push($request);

        $uniqueName = $this->nameStrategy->getGridUniqueName('name');
        $this->assertEquals('name_w1', $uniqueName);
    }
}
