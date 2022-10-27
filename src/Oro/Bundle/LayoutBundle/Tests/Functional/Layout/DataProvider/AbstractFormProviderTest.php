<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Tests\Functional\Layout\DataProvider\Stubs\FormProviderStub;
use Oro\Bundle\LayoutBundle\Tests\Functional\Layout\DataProvider\Stubs\LayoutFormStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AbstractFormProviderTest extends WebTestCase
{
    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var FormProviderStub */
    private $formProvider;

    protected function setUp(): void
    {
        $this->initClient();

        $this->router = $this->createMock(UrlGeneratorInterface::class);

        $this->formFactory = $this->getContainer()->get('form.factory');
        $this->formProvider = new FormProviderStub($this->formFactory, $this->router);
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testGetFormAndFormView(
        string $routeName,
        mixed $data,
        array $options,
        array $cacheKeyOptions,
        array $expectedData,
        string $expectedCacheKey
    ) {
        $formAction = 'form_action';

        $options = array_merge($options, ['action' => $formAction]);

        $this->router->expects($this->exactly(4))
            ->method('generate')
            ->with($routeName)
            ->willReturn($formAction);

        $resultForm = $this->formProvider->getTestForm($routeName, $data, $options, $cacheKeyOptions);

        $expectedForm = $this->getForm($expectedData['data'], $expectedData['options']);

        // check local cache
        $cachedForm = $this->formProvider->getTestForm($routeName, $data, $options, $cacheKeyOptions);

        // check local cache for form view
        $cachedFormView1 = $this->formProvider
            ->getTestFormView($routeName, $data, $options, $cacheKeyOptions);
        $cachedFormView2 = $this->formProvider
            ->getTestFormView($routeName, $data, $options, $cacheKeyOptions);

        $this->assertEquals($expectedForm, $resultForm);
        $this->assertSame($expectedForm->getData(), $resultForm->getData());
        $this->assertSame($resultForm, $cachedForm);
        $this->assertSame($cachedFormView1, $cachedFormView2);
        $this->assertSame(
            $expectedCacheKey,
            $this->formProvider->getTestCacheKey($options, $cacheKeyOptions)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function formDataProvider(): array
    {
        return [
            'form' => [
                'routeName' => 'form_name',
                'data' => null,
                'options' => [],
                'cacheKeyOptions' => [],
                'expectedData' => [
                    'data' => null,
                    'options' => [
                        'action' => 'form_action',
                    ],
                ],
                'expectedCacheKey' => $this->getCacheKey(LayoutFormStub::class, [
                    'action' => 'form_action'
                ])
            ],
            'form with data' => [
                'routeName' => 'form_name',
                'data' => [
                    'test_data' => 'Test Data',
                ],
                'options' => [],
                'cacheKeyOptions' => [],
                'expectedData' => [
                    'data' => [
                        'test_data' => 'Test Data',
                    ],
                    'options' => [
                        'action' => 'form_action',
                    ],
                ],
                'expectedCacheKey' => $this->getCacheKey(LayoutFormStub::class, [
                    'action' => 'form_action'
                ])
            ],
            'form with options' => [
                'routeName' => 'form_name',
                'data' => null,
                'options' => [
                    'attr' => [
                        'attr1' => 'Attr 1',
                        'attr2' => 'Attr 2',
                    ]
                ],
                'cacheKeyOptions' => [],
                'expectedData' => [
                    'data' => null,
                    'options' => [
                        'action' => 'form_action',
                        'attr' => [
                            'attr1' => 'Attr 1',
                            'attr2' => 'Attr 2',
                        ]
                    ],
                ],
                'expectedCacheKey' => $this->getCacheKey(LayoutFormStub::class, [
                    'action' => 'form_action',
                    'attr' => [
                        'attr1' => 'Attr 1',
                        'attr2' => 'Attr 2',
                    ]
                ])
            ],
            'form with cache options' => [
                'routeName' => 'form_name',
                'data' => null,
                'options' => [
                    'attr' => [
                        'attr1' => 'Attr 1',
                        'attr2' => 'Attr 2',
                    ]
                ],
                'cacheKeyOptions' => [
                    'cache_option1' => 'Cache Option 1',
                ],
                'expectedData' => [
                    'data' => null,
                    'options' => [
                        'action' => 'form_action',
                        'attr' => [
                            'attr1' => 'Attr 1',
                            'attr2' => 'Attr 2',
                        ]
                    ]
                ],
                'expectedCacheKey' => $this->getCacheKey(LayoutFormStub::class, [
                    'action' => 'form_action',
                    'attr' => [
                        'attr1' => 'Attr 1',
                        'attr2' => 'Attr 2',
                    ],
                    'cache_option1' => 'Cache Option 1',
                ])
            ],
            'form with overridden options for cache' => [
                'routeName' => 'form_name',
                'data' => null,
                'options' => [
                    'attr' => [
                        'attr1' => 'Attr 1',
                        'attr2' => 'Attr 2',
                    ]
                ],
                'cacheKeyOptions' => [
                    'attr' => [],
                    'cache_option1' => 'Cache Option 1',
                ],
                'expectedData' => [
                    'data' => null,
                    'options' => [
                        'action' => 'form_action',
                        'attr' => [
                            'attr1' => 'Attr 1',
                            'attr2' => 'Attr 2',
                        ]
                    ]
                ],
                'expectedCacheKey' => $this->getCacheKey(LayoutFormStub::class, [
                    'action' => 'form_action',
                    'attr' => [],
                    'cache_option1' => 'Cache Option 1',
                ])
            ]
        ];
    }

    private function getForm(mixed $data = null, array $options = []): FormInterface
    {
        return $this->formFactory->create(LayoutFormStub::class, $data, $options);
    }

    private function getCacheKey(string $type, array $options): string
    {
        return sprintf('%s:%s', $type, md5(serialize($options)));
    }
}
