<?php

namespace Oro\Bundle\ActionBundle\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Operation\Execution\FormProvider;
use Oro\Component\Action\Exception as ActionException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Exception as FormException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handle operation execution request. Provide ExecuteOperationResult object after operation is processed with
 * set of result variables.
 */
class ExecuteOperationHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var FormProvider */
    protected $formProvider;

    /** @var ContextHelper */
    protected $contextHelper;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param RequestStack    $requestStack
     * @param FormProvider    $formProvider
     * @param ContextHelper   $helper
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestStack $requestStack,
        FormProvider $formProvider,
        ContextHelper $helper,
        LoggerInterface $logger
    ) {
        $this->requestStack = $requestStack;
        $this->formProvider = $formProvider;
        $this->contextHelper = $helper;
        $this->logger = $logger;
    }

    /**
     * Process operation execution request
     *
     * @param Operation $operation
     *
     * @return ExecuteOperationResult
     */
    public function process(Operation $operation): ExecuteOperationResult
    {
        $data = $this->contextHelper->getActionData();
        $errors = new ArrayCollection();
        $result = new ExecuteOperationResult(true, Response::HTTP_OK, $data);
        $result->setValidationErrors($errors);
        try {
            $this->validateOperation($operation, $data, $result);
            $operation->execute($data, $errors);
        } catch (ForbiddenOperationException $e) {
            $this->setFailResult($result, $operation, $e, Response::HTTP_FORBIDDEN);
        } catch (FormException\AlreadySubmittedException $e) {
            $this->setFailResult($result, $operation, $e, Response::HTTP_BAD_REQUEST);
        } catch (ActionException\InvalidConfigurationException $e) {
            $this->setFailResult($result, $operation, $e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $result->setPageReload($operation->getDefinition()->isPageReload());

        return $result;
    }

    /**
     * @param Operation              $operation
     * @param ActionData             $data
     * @param ExecuteOperationResult $result
     *
     * @throws \Symfony\Component\Form\Exception\AlreadySubmittedException
     * @throws \Oro\Component\Action\Exception\InvalidConfigurationException
     * @throws \Oro\Bundle\ActionBundle\Exception\ForbiddenOperationException
     */
    protected function validateOperation(Operation $operation, ActionData $data, ExecuteOperationResult $result)
    {
        $isValidForm = true;
        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request) {
            $executionForm = $this->formProvider->getOperationExecutionForm($operation, $data);
            $executionForm->handleRequest($request);
            $isValidForm = $executionForm->isSubmitted() && $executionForm->isValid();
        }
        if (!$isValidForm || !$operation->isAvailable($data, $result->getValidationErrors())) {
            throw new ForbiddenOperationException(
                sprintf('Operation "%s" execution is forbidden!', $operation->getName())
            );
        }
    }

    /**
     * @param ExecuteOperationResult $result
     * @param Operation              $operation
     * @param \Exception             $reason
     * @param int                    $code
     */
    protected function setFailResult(
        ExecuteOperationResult $result,
        Operation $operation,
        \Exception $reason,
        int $code
    ) {
        $logContext = ['operation' => $operation->getName(), 'exception' => $reason];
        foreach ($result->getValidationErrors() as $error) {
            $logContext['validationErrors'][] = $error['message'];
        }
        $this->logger->warning('Execution of operation "{operation}" failed', $logContext);

        $result->setCode($code);
        $result->setSuccess(false);
        $result->setExceptionMessage($reason->getMessage());
    }
}
