<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Provider;

use Oro\Bundle\FormBundle\Model\FormTemplateDataProviderResolver;
use Oro\Bundle\FormBundle\Provider\CallbackFormTemplateDataProvider;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderComposite;
use Oro\Bundle\FormBundle\Provider\FormTemplateDataProviderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class FormTemplateDataProviderCompositeTest extends \PHPUnit\Framework\TestCase
{
    private FormTemplateDataProviderResolver|\PHPUnit\Framework\MockObject\MockObject $formTemplateDataProviderResolver;

    private FormTemplateDataProviderComposite $providerComposite;

    protected function setUp(): void
    {
        $this->formTemplateDataProviderResolver = $this->createMock(FormTemplateDataProviderResolver::class);

        $this->providerComposite = new FormTemplateDataProviderComposite($this->formTemplateDataProviderResolver);
    }

    public function testGetDataNoProviders(): void
    {
        $this->formTemplateDataProviderResolver->expects(self::never())
            ->method('resolve')
            ->withAnyParameters();

        self::assertEmpty(
            $this->providerComposite->getData(
                new \stdClass(),
                $this->createMock(FormInterface::class),
                $this->createMock(Request::class)
            )
        );
    }

    public function testGetData(): void
    {
        $entity = new \stdClass();
        $form = $this->createMock(FormInterface::class);
        $request = $this->createMock(Request::class);

        $providerByDefaultAlias = $this->createMock(FormTemplateDataProviderInterface::class);
        $providerByDefaultAlias->expects(self::once())
            ->method('getData')
            ->with($entity, $form, $request)
            ->willReturn(
                [
                    'basicEntity' => \stdClass::class,
                    'basicForm' => $this->createMock(FormInterface::class),
                    'providerByDefaultAlias' => 'bar',
                ]
            );

        $provider = $this->createMock(FormTemplateDataProviderInterface::class);
        $provider->expects(self::once())
            ->method('getData')
            ->with($entity, $form, $request)
            ->willReturn(
                [
                    'basicEntity' => \stdClass::class,
                    'basicForm' => $this->createMock(FormInterface::class),
                    'provider' => 'bar',
                ]
            );

        $callback = function () {
            return [
                'basicEntity' => \stdClass::class,
                'basicForm' => $this->createMock(FormInterface::class),
                'providerByCallback' => 'bar',
            ];
        };
        $providerByCallback = new CallbackFormTemplateDataProvider($callback);

        $alias = 'provider_alias';
        $providerByAlias = $this->createMock(FormTemplateDataProviderInterface::class);
        $providerByAlias->expects(self::once())
            ->method('getData')
            ->with($entity, $form, $request)
            ->willReturn(
                [
                    'providerByAlias' => 'bar',
                ]
            );

        $this->formTemplateDataProviderResolver->expects(self::exactly(4))
            ->method('resolve')
            ->willReturnMap([
                [null, $providerByDefaultAlias],
                [$provider, $provider],
                [$callback, $providerByCallback],
                [$alias, $providerByAlias],
            ]);

        $this->providerComposite
            ->addFormTemplateDataProviders(null)
            ->addFormTemplateDataProviders($provider)
            ->addFormTemplateDataProviders($callback)
            ->addFormTemplateDataProviders($alias);

        $expectedData = [
            'basicEntity' => \stdClass::class,
            'basicForm' => $this->createMock(FormInterface::class),
            'providerByDefaultAlias' => 'bar',
            'provider' => 'bar',
            'providerByCallback' => 'bar',
            'providerByAlias' => 'bar',
        ];

        self::assertEquals($expectedData, $this->providerComposite->getData($entity, $form, $request));
    }
}
