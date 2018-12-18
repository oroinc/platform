<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Handler\ConfigFieldHandler;
use Oro\Bundle\EntityConfigBundle\Form\Handler\ConfigHelperHandler;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ConfigFieldHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const SAMPLE_FORM_ACTION = '/entity_config/create';
    const SAMPLE_SUCCESS_MESSAGE = 'Entity config was successfully saved';

    /**
     * @var ConfigHelperHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configHelperHandler;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestStack;

    /**
     * @var FieldConfigModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldConfigModel;

    /**
     * @var ConfigFieldHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->configHelperHandler = $this->createMock(ConfigHelperHandler::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->fieldConfigModel = $this->getEntity(FieldConfigModel::class, ['id' => 777]);

        $this->handler = new ConfigFieldHandler(
            $this->configHelperHandler,
            $this->requestStack
        );
    }

    /**
     * @param bool $isFormValid
     * @return FormInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function expectsFormCreationSubmissionAndValidation($isFormValid)
    {
        $form = $this->createMock(FormInterface::class);
        $this->configHelperHandler
            ->expects($this->once())
            ->method('createSecondStepFieldForm')
            ->with($this->fieldConfigModel)
            ->willReturn($form);

        $request = new Request();
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->configHelperHandler
            ->expects($this->once())
            ->method('isFormValidAfterSubmit')
            ->with($request, $form)
            ->willReturn($isFormValid);

        return $form;
    }

    public function testHandleUpdateWhenFormIsValid()
    {
        $this->expectsFormCreationSubmissionAndValidation(true);
        $successMessage = 'Success message';

        $redirectResponse = new RedirectResponse('someurl');
        $this->configHelperHandler
            ->expects($this->once())
            ->method('showSuccessMessageAndRedirect')
            ->with($this->fieldConfigModel, $successMessage)
            ->willReturn($redirectResponse);

        $this->configHelperHandler
            ->expects($this->once())
            ->method('showClearCacheMessage')
            ->willReturn($this->configHelperHandler);

        $formAction = 'formAction';
        $response = $this->handler->handleUpdate($this->fieldConfigModel, $formAction, $successMessage);

        $this->assertEquals($redirectResponse->getTargetUrl(), $response->getTargetUrl());
        $this->assertEquals($redirectResponse->getStatusCode(), $response->getStatusCode());
    }

    public function testHandleUpdateWhenFormIsNotValid()
    {
        $form = $this->expectsFormCreationSubmissionAndValidation(false);
        $successMessage = 'Success message';

        $arrayResponse = [
            'entity_config' => 'Entity config'
        ];

        $formAction = 'formAction';
        $this->configHelperHandler
            ->expects($this->once())
            ->method('constructConfigResponse')
            ->with($this->fieldConfigModel, $form, $formAction)
            ->willReturn($arrayResponse);

        $this->assertEquals(
            $arrayResponse,
            $this->handler->handleUpdate($this->fieldConfigModel, $formAction, $successMessage)
        );
    }
}
