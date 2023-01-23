<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Provider;

use Oro\Bundle\FormBundle\Provider\SaveAndReturnActionFormTemplateDataProvider;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

class SaveAndReturnActionFormTemplateDataProviderTest extends \PHPUnit\Framework\TestCase
{
    private FormInterface|\PHPUnit\Framework\MockObject\MockObject $form;

    private Request|\PHPUnit\Framework\MockObject\MockObject $request;

    private SaveAndReturnActionFormTemplateDataProvider $provider;

    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = $this->createMock(Request::class);

        $this->provider = new SaveAndReturnActionFormTemplateDataProvider();
    }

    public function testGetDataWithoutData(): void
    {
        $entity = new \stdClass();

        $formView = $this->createMock(FormView::class);

        $this->form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        self::assertEquals(
            [
                'entity' => $entity,
                'form' => $formView,
            ],
            $this->provider->getData($entity, $this->form, $this->request)
        );
    }

    public function testGetDataWithReturnActionData(): void
    {
        $entity = new \stdClass();

        $formView = $this->createMock(FormView::class);

        $this->form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $returnActionRoute = 'oro_form_test_route';
        $returnActionRouteParameters = [
            'id' => PHP_INT_MAX,
            'foo' => 'bar',
        ];
        $returnActionAclRole = 'oro_form_test_route_acl_role';
        $this->provider->setReturnActionRoute($returnActionRoute, $returnActionRouteParameters, $returnActionAclRole);

        self::assertEquals(
            [
                'entity' => $entity,
                'form' => $formView,
                'returnAction' => [
                    'route' => $returnActionRoute,
                    'parameters' => $returnActionRouteParameters,
                    'aclRole' => $returnActionAclRole,
                ],
            ],
            $this->provider->getData($entity, $this->form, $this->request)
        );
    }

    public function testGetDataWithSaveFormActionData(): void
    {
        $entity = new \stdClass();

        $formView = $this->createMock(FormView::class);

        $this->form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $saveFormActionRoute = 'oro_form_save_test_route';
        $saveFormActionRouteParameters = [
            'id' => PHP_INT_MAX,
            'bar' => 'foo',
        ];
        $this->provider->setSaveFormActionRoute($saveFormActionRoute, $saveFormActionRouteParameters);

        self::assertEquals(
            [
                'entity' => $entity,
                'form' => $formView,
                'saveFormAction' => [
                    'route' => $saveFormActionRoute,
                    'parameters' => $saveFormActionRouteParameters,
                ],
            ],
            $this->provider->getData($entity, $this->form, $this->request)
        );
    }

    public function testGetData(): void
    {
        $entity = new \stdClass();

        $formView = $this->createMock(FormView::class);

        $this->form->expects(self::once())
            ->method('createView')
            ->willReturn($formView);

        $returnActionRoute = 'oro_form_test_route';
        $returnActionRouteParameters = [
            'id' => PHP_INT_MAX,
            'foo' => 'bar',
        ];
        $returnActionAclRole = 'oro_form_test_route_acl_role';

        $saveFormActionRoute = 'oro_form_save_test_route';
        $saveFormActionRouteParameters = [
            'id' => PHP_INT_MAX,
            'bar' => 'foo',
        ];

        $this->provider
            ->setReturnActionRoute($returnActionRoute, $returnActionRouteParameters, $returnActionAclRole)
            ->setSaveFormActionRoute($saveFormActionRoute, $saveFormActionRouteParameters);

        self::assertEquals(
            [
                'entity' => $entity,
                'form' => $formView,
                'returnAction' => [
                    'route' => $returnActionRoute,
                    'parameters' => $returnActionRouteParameters,
                    'aclRole' => $returnActionAclRole,
                ],
                'saveFormAction' => [
                    'route' => $saveFormActionRoute,
                    'parameters' => $saveFormActionRouteParameters,
                ],
            ],
            $this->provider->getData($entity, $this->form, $this->request)
        );
    }
}
