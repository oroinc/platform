<?php

namespace Oro\Bundle\ActionBundle\Operation\Execution;

use Oro\Bundle\ActionBundle\Form\Type\OperationExecutionType;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Component\Action\Exception\InvalidConfigurationException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * Provides csrf protected operation execution form and csrf token data which is used
 * for manual creation operation execution form's data.
 */
class FormProvider
{
    const CSRF_TOKEN_FIELD = 'operation_execution_csrf_token';

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var string */
    protected $formTypeClass;

    /**
     * @param FormFactoryInterface   $formFactory
     * @param string $formTypeClass
     */
    public function __construct(FormFactoryInterface $formFactory, string $formTypeClass)
    {
        $this->formFactory = $formFactory;
        $this->formTypeClass = $formTypeClass;
    }

    /**
     * Returns csrf protected form for operation execution.
     *
     * @param Operation  $operation
     * @param ActionData $actionData
     *
     * @return FormInterface
     * @throws InvalidConfigurationException
     */
    public function getOperationExecutionForm(Operation $operation, ActionData $actionData): FormInterface
    {
        $tokenId = $this->getTokenId($operation, $actionData->getOperationToken());
        $options = ['csrf_token_id' => $tokenId];

        try {
            return $this->formFactory->create($this->formTypeClass, $operation, $options);
        } catch (InvalidOptionsException $e) {
            throw new InvalidConfigurationException('Invalid execution form options', $e->getCode(), $e);
        }
    }

    /**
     * Creates data of operation execution csrf protection parameters
     * generated using form functionality.
     *
     * @param Operation  $operation
     * @param ActionData $actionData
     *
     * @return array
     * @throws InvalidOptionsException
     */
    public function createTokenData(Operation $operation, ActionData $actionData): array
    {
        $tokenId  = $this->getTokenId($operation, $actionData->getOperationToken());
        $options  = ['csrf_token_id'   => $tokenId];
        $form = $this->formFactory->create($this->formTypeClass, $operation, $options);
        $formView = $form->createView();
        $token    = $formView->children[self::CSRF_TOKEN_FIELD];

        return [OperationExecutionType::NAME => [self::CSRF_TOKEN_FIELD => $token->vars['value']]];
    }

    /**
     * @param Operation $operation
     * @param string    $actionKey
     *
     * @return string
     */
    protected function getTokenId(Operation $operation, string $actionKey): string
    {
        return sprintf('%s_%s', $operation->getName(), $actionKey);
    }
}
