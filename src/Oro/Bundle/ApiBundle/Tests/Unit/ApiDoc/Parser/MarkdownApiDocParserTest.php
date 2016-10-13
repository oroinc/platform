<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Symfony\Component\HttpKernel\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\ApiBundle\ApiDoc\Parser\MarkdownApiDocParser;

class MarkdownApiDocParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return MarkdownApiDocParser
     */
    protected function loadDocument()
    {
        $inputPath = __DIR__ . '/Fixtures/apidoc.md';

        $fileLocator = $this->getMockBuilder(FileLocator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fileLocator->expects($this->once())
            ->method('locate')
            ->with('@OroApiBundle/Tests/Unit/ApiDoc/Parser/Fixtures/apidoc.md')
            ->willReturn($inputPath);

        $apiDocParser = new MarkdownApiDocParser($fileLocator);
        $apiDocParser->parseDocumentationResource('@OroApiBundle/Tests/Unit/ApiDoc/Parser/Fixtures/apidoc.md');

        return $apiDocParser;
    }

    public function testParse()
    {
        $apiDocParser = $this->loadDocument();

        $expected = Yaml::parse(file_get_contents(__DIR__ . '/Fixtures/apidoc.yml'));

        $this->assertAttributeEquals(
            $expected,
            'loadedData',
            $apiDocParser
        );
    }

    /**
     * @dataProvider getActionDocumentationProvider
     */
    public function testGetActionDocumentation($expected, $className, $actionName)
    {
        $apiDocParser = $this->loadDocument();

        $this->assertSame(
            $expected,
            $apiDocParser->getActionDocumentation($className, $actionName)
        );
    }

    public function getActionDocumentationProvider()
    {
        return [
            'known action'                     => [
                '<p>Description for GET_LIST action</p><p><strong>text in bold</strong></p>',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'get_list'
            ],
            'names should be case insensitive' => [
                '<p>Description for GET_LIST action</p><p><strong>text in bold</strong></p>',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'GET_LIST'
            ],
            'unknown action'                   => [
                null,
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'unknown'
            ],
            'unknown actions group'            => [
                null,
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group',
                'get'
            ],
            'unknown class'                    => [
                null,
                'Test\UnknownClass',
                'get'
            ],
        ];
    }

    /**
     * @dataProvider getFieldDocumentationProvider
     */
    public function testGetFieldDocumentation($expected, $className, $fieldName, $actionName = null)
    {
        $apiDocParser = $this->loadDocument();

        $this->assertSame(
            $expected,
            $apiDocParser->getFieldDocumentation($className, $fieldName, $actionName)
        );
    }

    public function getFieldDocumentationProvider()
    {
        return [
            'only common doc exists'                                               => [
                '<p>Description for ID field</p>',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'id',
                'get'
            ],
            'only common doc exists (requested common doc)'                        => [
                '<p>Description for ID field</p>',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'id'
            ],
            'common doc should be used'                                            => [
                '<p>Description for NAME field</p>',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'name',
                'get'
            ],
            'common doc should be used (requested common doc)'                     => [
                '<p>Description for NAME field</p>',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'name'
            ],
            'action doc should be used'                                            => [
                '<p>Description for NAME field for DELETE action</p>',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'name',
                'delete'
            ],
            'action doc should be used (the first action in #### create, update)'  => [
                '<p>Description for NAME field for CREATE and UPDATE actions</p>',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'name',
                'create'
            ],
            'action doc should be used (the second action in #### create, update)' => [
                '<p>Description for NAME field for CREATE and UPDATE actions</p>',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'name',
                'update'
            ],
            'names should be case insensitive'                                     => [
                '<p>Description for NAME field for CREATE and UPDATE actions</p>',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'NAME',
                'CREATE'
            ],
            'unknown field'                                                        => [
                null,
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'unknown',
                'get'
            ],
            'unknown fields group'                                                 => [
                null,
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group',
                'name',
                'get'
            ],
            'unknown class'                                                        => [
                null,
                'Test\UnknownClass',
                'name',
                'get'
            ],
        ];
    }

    /**
     * @dataProvider getFilterDocumentationProvider
     */
    public function testGetFilterDocumentation($expected, $className, $filterName)
    {
        $apiDocParser = $this->loadDocument();

        $this->assertSame(
            $expected,
            $apiDocParser->getFilterDocumentation($className, $filterName)
        );
    }

    public function getFilterDocumentationProvider()
    {
        return [
            'known filter'                     => [
                'Description for NAME filter',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'name'
            ],
            'names should be case insensitive' => [
                'Description for NAME filter',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'NAME'
            ],
            'unknown field'                    => [
                null,
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'unknown'
            ],
            'unknown filters group'            => [
                null,
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group',
                'name'
            ],
            'unknown class'                    => [
                null,
                'Test\UnknownClass',
                'name'
            ],
        ];
    }

    /**
     * @dataProvider getSubresourceDocumentationProvider
     */
    public function testGetSubresourceDocumentation($expected, $className, $subresourceName, $actionName)
    {
        $apiDocParser = $this->loadDocument();

        $this->assertSame(
            $expected,
            $apiDocParser->getSubresourceDocumentation($className, $subresourceName, $actionName)
        );
    }

    public function getSubresourceDocumentationProvider()
    {
        return [
            'known sub-resource'               => [
                '<p>Description for <em>contacts GET_SUBRESOURCE</em> sub-resource</p>',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'contacts',
                'get_subresource'
            ],
            'names should be case insensitive' => [
                '<p>Description for <em>contacts GET_SUBRESOURCE</em> sub-resource</p>',
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'CONTACTS',
                'GET_SUBRESOURCE'
            ],
            'unknown sub-resource'             => [
                null,
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Account',
                'unknown',
                'get_subresource'
            ],
            'unknown subresources group'       => [
                null,
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group',
                'contacts',
                'get_subresource'
            ],
            'unknown class'                    => [
                null,
                'Test\UnknownClass',
                'contacts',
                'get_subresource'
            ],
        ];
    }
}
