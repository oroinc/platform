<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\TestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ConfigBundle\Api\Processor\GetScope;

class GetScopeTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configRepository;

    /** @var GetScope */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->configRepository = $this
            ->getMockBuilder('Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new GetScope($this->configRepository);
    }

    public function testProcessDefaultValue()
    {
        $defaultScope = 'test';

        $this->context->getFilters()
            ->add(
                'scope',
                new StandaloneFilterWithDefaultValue(
                    'string',
                    'filter description',
                    $defaultScope
                )
            );
        $this->processor->process($this->context);

        $this->assertEquals($defaultScope, $this->context->get(GetScope::CONTEXT_PARAM));
    }

    public function testProcessWhenScopeExistsInInputData()
    {
        $scope = 'test';
        $supportedScopes = ['test', 'test1'];

        $this->configRepository->expects($this->once())
            ->method('getScopes')
            ->willReturn($supportedScopes);

        $this->context->getFilters()
            ->add(
                'scope',
                new StandaloneFilterWithDefaultValue(
                    'string',
                    'filter description',
                    'default_scope'
                )
            );
        $this->context->setFilterValues(new TestFilterValueAccessor());
        $this->context->getFilterValues()->set('scope', new FilterValue('scope', $scope));
        $this->processor->process($this->context);

        $this->assertEquals($scope, $this->context->get(GetScope::CONTEXT_PARAM));
    }

    public function testProcessForUnknownScope()
    {
        $scope = 'unknown';
        $supportedScopes = ['test', 'test1'];

        $this->configRepository->expects($this->once())
            ->method('getScopes')
            ->willReturn($supportedScopes);

        $this->context->getFilters()
            ->add(
                'scope',
                new StandaloneFilterWithDefaultValue(
                    'string',
                    'filter description',
                    'default_scope'
                )
            );
        $this->context->setFilterValues(new TestFilterValueAccessor());
        $this->context->getFilterValues()->set('scope', new FilterValue('scope', $scope));
        $this->processor->process($this->context);

        $this->assertFalse($this->context->has(GetScope::CONTEXT_PARAM));
        $this->assertEquals(
            [
                Error::createValidationError(
                    Constraint::FILTER,
                    sprintf('Unknown configuration scope. Permissible values: %s.', implode(', ', $supportedScopes))
                )->setSource(ErrorSource::createByParameter('scope'))
            ],
            $this->context->getErrors()
        );
    }
}
