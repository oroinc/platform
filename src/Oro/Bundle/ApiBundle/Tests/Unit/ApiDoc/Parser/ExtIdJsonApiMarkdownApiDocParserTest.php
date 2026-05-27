<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\ExtIdJsonApiMarkdownApiDocParser;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\MarkdownApiDocParser;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;

class ExtIdJsonApiMarkdownApiDocParserTest extends TestCase
{
    private RequestType $requestType;
    private MarkdownApiDocParser $resultApiDocParser;
    private ExtIdJsonApiMarkdownApiDocParser $apiDocParser;

    #[\Override]
    protected function setUp(): void
    {
        $fileLocator = new FileLocator([__DIR__ . '/Fixtures/ext_id_json_api']);

        $this->requestType = new RequestType(['rest', 'json_api', 'ext_id']);
        $this->resultApiDocParser = new MarkdownApiDocParser($fileLocator);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('isManageableEntityClass')
            ->willReturn(true);
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::any())
            ->method('isInheritanceTypeNone')
            ->willReturn(true);
        $doctrineHelper->expects(self::any())
            ->method('getEntityMetadataForClass')
            ->willReturn($classMetadata);

        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with(self::isType('string'), DataType::ENTITY_TYPE, self::identicalTo($this->requestType))
            ->willReturnCallback(function ($value) {
                return strtolower(str_replace('Test\\', '', $value)) . 's';
            });

        $this->apiDocParser = new ExtIdJsonApiMarkdownApiDocParser(
            new MarkdownApiDocParser($fileLocator),
            ['Test\User' => 'external_id', 'Test\Product' => 'sku'],
            $doctrineHelper,
            $valueNormalizer
        );
        $this->apiDocParser->setRequestType($this->requestType);
        $this->apiDocParser->setIdValue('Test\Product', 'TEST_SKU');
        $this->apiDocParser->setAttributesToRemove('Test\Product', ['sku']);
    }

    /**
     * @dataProvider getActionDocumentationDataProvider
     */
    public function testGetActionDocumentation(
        string $className,
        string $action,
        string $resource,
        string $resultResourceSuffix = '_result'
    ): void {
        $this->apiDocParser->registerDocumentationResource($resource);
        $this->resultApiDocParser->registerDocumentationResource(
            str_replace('.md', $resultResourceSuffix . '.md', $resource)
        );

        $documentation = $this->apiDocParser->getActionDocumentation($className, $action);
        $expectedDocumentation = $this->resultApiDocParser->getActionDocumentation($className, $action);

        self::assertEquals($expectedDocumentation, $documentation);
        self::assertNotEmpty($documentation);
    }

    public static function getActionDocumentationDataProvider(): array
    {
        return [
            ['Test\User', ApiAction::CREATE, 'create_no_example.md', ''],
            ['Test\User', ApiAction::UPDATE, 'update_no_example.md', ''],
            ['Test\User', ApiAction::CREATE, 'create.md'],
            ['Test\User', ApiAction::UPDATE, 'update.md'],
            ['Test\User', ApiAction::CREATE, 'create_several_examples.md'],
            ['Test\User', ApiAction::UPDATE, 'update_several_examples.md'],
            ['Test\User', ApiAction::CREATE, 'create_several_examples_in_one_block.md'],
            ['Test\User', ApiAction::UPDATE, 'update_several_examples_in_one_block.md'],
            ['Test\Product', ApiAction::CREATE, 'create_custom_external_id.md'],
            ['Test\Product', ApiAction::UPDATE, 'update_custom_external_id.md'],
        ];
    }
}
