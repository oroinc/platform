<?php


namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\GetList\JsonApi\BuildJsonApiDocument;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\JsonApi\BuildJsonApiDocumentTest as ParentTest;
use Oro\Component\ChainProcessor\ProcessorInterface;

class BuildJsonApiDocumentTest extends ParentTest
{
    /**
     * @return ProcessorInterface
     */
    protected function getProcessor()
    {
        return new BuildJsonApiDocument($this->documentBuilderFactory);
    }

    /**
     * @return Context
     */
    protected function getContext()
    {
        $configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        return new GetListContext($configProvider, $metadataProvider);
    }

    /**
     * @return string
     */
    protected function getSetterDataFunctionName()
    {
        return 'setDataCollection';
    }
}
