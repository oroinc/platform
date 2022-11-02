<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Oro\Bundle\ApiBundle\ApiDoc\Parser\MarkdownApiDocParser;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

class MarkdownApiDocParserTest extends \PHPUnit\Framework\TestCase
{
    private function loadDocument(): MarkdownApiDocParser
    {
        $fixturesDir = __DIR__ . '/Fixtures';

        $fileLocator = $this->createMock(FileLocator::class);
        $fileLocator->expects(self::any())
            ->method('locate')
            ->willReturnCallback(function ($resource) use ($fixturesDir) {
                return str_replace(
                    '@OroApiBundle/Tests/Unit/ApiDoc/Parser/Fixtures',
                    $fixturesDir,
                    $resource
                );
            });

        $apiDocParser = new MarkdownApiDocParser($fileLocator);
        self::assertTrue(
            $apiDocParser->registerDocumentationResource('@OroApiBundle/Tests/Unit/ApiDoc/Parser/Fixtures/apidoc.md')
        );

        return $apiDocParser;
    }

    /**
     * Assert loaded data in markdown parser
     *
     * In the PHP >= 7.3 updated DOM component.
     * Now DOMDocument::saveHTML return html in different formatting than PHP < 7.3.
     * We remove new lines in actual parsed data so that check to work on all supported PHP versions.
     */
    private function assertLoadedData(array $expected, MarkdownApiDocParser $apiDocParser): void
    {
        $actualValue = ReflectionUtil::getPropertyValue($apiDocParser, 'loadedData');

        $normalizer = static function (&$val): void {
            if (\is_string($val)) {
                $val = str_replace("\n", '', $val);
            }
        };

        array_walk_recursive($expected, $normalizer);
        array_walk_recursive($actualValue, $normalizer);

        self::assertEquals($expected, $actualValue);
    }

    public function testRegisterDocumentationResourceForUnsupportedFile()
    {
        $fileLocator = $this->createMock(FileLocator::class);
        $fileLocator->expects(self::never())
            ->method('locate');

        $apiDocParser = new MarkdownApiDocParser($fileLocator);
        self::assertFalse(
            $apiDocParser->registerDocumentationResource('@OroApiBundle/Tests/Unit/ApiDoc/Parser/Fixtures/apidoc.doc')
        );
    }

    public function testParse()
    {
        $apiDocParser = $this->loadDocument();

        $expected = Yaml::parse(file_get_contents(__DIR__ . '/Fixtures/apidoc.yml'));

        $this->assertLoadedData($expected, $apiDocParser);
    }

    public function testInheritDoc()
    {
        $apiDocParser = $this->loadDocument();
        $apiDocParser->registerDocumentationResource('@OroApiBundle/Tests/Unit/ApiDoc/Parser/Fixtures/inheritdoc.md');

        $expected = Yaml::parse(file_get_contents(__DIR__ . '/Fixtures/inheritdoc.yml'));

        $this->assertLoadedData($expected, $apiDocParser);
    }

    public function testReplaceDescriptions()
    {
        $apiDocParser = $this->loadDocument();
        $apiDocParser->registerDocumentationResource('@OroApiBundle/Tests/Unit/ApiDoc/Parser/Fixtures/replace.md');

        $expected = Yaml::parse(file_get_contents(__DIR__ . '/Fixtures/replace.yml'));

        $this->assertLoadedData($expected, $apiDocParser);
    }

    /**
     * @dataProvider getActionDocumentationProvider
     */
    public function testGetActionDocumentation(?string $expected, string $className, string $actionName)
    {
        $apiDocParser = $this->loadDocument();

        self::assertSame(
            $expected,
            $apiDocParser->getActionDocumentation($className, $actionName)
        );
    }

    public function getActionDocumentationProvider(): array
    {
        return [
            'known action'                     => [
                '<p>Description for GET_LIST action</p><p><strong>text in bold</strong></p>',
                Entity\Account::class,
                'get_list'
            ],
            'names should be case insensitive' => [
                '<p>Description for GET_LIST action</p><p><strong>text in bold</strong></p>',
                Entity\Account::class,
                'GET_LIST'
            ],
            'unknown action'                   => [
                null,
                Entity\Account::class,
                'unknown'
            ],
            'unknown actions group'            => [
                null,
                Entity\Group::class,
                'get'
            ],
            'unknown class'                    => [
                null,
                'Test\UnknownClass',
                'get'
            ]
        ];
    }

    /**
     * @dataProvider getFieldDocumentationProvider
     */
    public function testGetFieldDocumentation(
        ?string $expected,
        string $className,
        string $fieldName,
        ?string $actionName = null
    ) {
        $apiDocParser = $this->loadDocument();

        self::assertSame(
            $expected,
            $apiDocParser->getFieldDocumentation($className, $fieldName, $actionName)
        );
    }

    public function getFieldDocumentationProvider(): array
    {
        return [
            'only common doc exists'                                               => [
                null,
                Entity\Account::class,
                'id',
                'get'
            ],
            'only common doc exists (requested common doc)'                        => [
                '<p>Description for ID field</p>',
                Entity\Account::class,
                'id'
            ],
            'common doc should not be used'                                        => [
                null,
                Entity\Account::class,
                'name',
                'get'
            ],
            'common doc should be returned if it requested directly'               => [
                '<p>Description for NAME field</p>',
                Entity\Account::class,
                'name'
            ],
            'action doc should be used'                                            => [
                '<p>Description for NAME field for DELETE action</p>',
                Entity\Account::class,
                'name',
                'delete'
            ],
            'action doc should be used (the first action in #### create, update)'  => [
                '<p>Description for NAME field for CREATE and UPDATE actions</p>',
                Entity\Account::class,
                'name',
                'create'
            ],
            'action doc should be used (the second action in #### create, update)' => [
                '<p>Description for NAME field for CREATE and UPDATE actions</p>',
                Entity\Account::class,
                'name',
                'update'
            ],
            'names should be case insensitive'                                     => [
                '<p>Description for NAME field for CREATE and UPDATE actions</p>',
                Entity\Account::class,
                'NAME',
                'CREATE'
            ],
            'unknown field'                                                        => [
                null,
                Entity\Account::class,
                'unknown',
                'get'
            ],
            'unknown fields group'                                                 => [
                null,
                Entity\Group::class,
                'name',
                'get'
            ],
            'unknown class'                                                        => [
                null,
                'Test\UnknownClass',
                'name',
                'get'
            ]
        ];
    }

    /**
     * @dataProvider getFilterDocumentationProvider
     */
    public function testGetFilterDocumentation(?string $expected, string $className, string $filterName)
    {
        $apiDocParser = $this->loadDocument();

        self::assertSame(
            $expected,
            $apiDocParser->getFilterDocumentation($className, $filterName)
        );
    }

    public function getFilterDocumentationProvider(): array
    {
        return [
            'known filter'                     => [
                'Description for NAME filter',
                Entity\Account::class,
                'name'
            ],
            'names should be case insensitive' => [
                'Description for NAME filter',
                Entity\Account::class,
                'NAME'
            ],
            'unknown field'                    => [
                null,
                Entity\Account::class,
                'unknown'
            ],
            'unknown filters group'            => [
                null,
                Entity\Group::class,
                'name'
            ],
            'unknown class'                    => [
                null,
                'Test\UnknownClass',
                'name'
            ]
        ];
    }

    /**
     * @dataProvider getSubresourceDocumentationProvider
     */
    public function testGetSubresourceDocumentation(
        ?string $expected,
        string $className,
        string $subresourceName,
        string $actionName
    ) {
        $apiDocParser = $this->loadDocument();

        self::assertSame(
            $expected,
            $apiDocParser->getSubresourceDocumentation($className, $subresourceName, $actionName)
        );
    }

    public function getSubresourceDocumentationProvider(): array
    {
        return [
            'known sub-resource'               => [
                '<p>Description for <em>contacts GET_SUBRESOURCE</em> sub-resource</p>',
                Entity\Account::class,
                'contacts',
                'get_subresource'
            ],
            'names should be case insensitive' => [
                '<p>Description for <em>contacts GET_SUBRESOURCE</em> sub-resource</p>',
                Entity\Account::class,
                'CONTACTS',
                'GET_SUBRESOURCE'
            ],
            'unknown sub-resource'             => [
                null,
                Entity\Account::class,
                'unknown',
                'get_subresource'
            ],
            'unknown subresources group'       => [
                null,
                Entity\Group::class,
                'contacts',
                'get_subresource'
            ],
            'unknown class'                    => [
                null,
                'Test\UnknownClass',
                'contacts',
                'get_subresource'
            ]
        ];
    }
}
