<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Generator;

use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\DataTypeDescribeHelper;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\DescriberInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer\ModelDescriberInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ErrorResponseStorage;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ErrorResponseStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\HeaderStorage;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\HeaderStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ModelStorage;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ModelStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ParameterStorage;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ParameterStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\RequestBodyStorage;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\RequestBodyStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ResponseStorage;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\ResponseStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\SchemaStorage;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Storage\SchemaStorageAwareInterface;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Util;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Generates OpenAPI specification.
 */
class OpenApiGenerator implements OpenApiGeneratorInterface
{
    /** @var iterable<DescriberInterface> */
    private iterable $describers;
    /** @var iterable<ModelDescriberInterface> */
    private iterable $modelDescribers;
    private DataTypeDescribeHelper $dataTypeDescribeHelper;
    private ?string $openApiVersion;

    public function __construct(
        iterable $describers,
        iterable $modelDescribers,
        DataTypeDescribeHelper $dataTypeDescribeHelper,
        ?string $openApiVersion = null
    ) {
        $this->describers = $describers;
        $this->modelDescribers = $modelDescribers;
        $this->dataTypeDescribeHelper = $dataTypeDescribeHelper;
        $this->openApiVersion = $openApiVersion;
    }

    #[\Override]
    public function generate(array $options = []): OA\OpenApi
    {
        $contextProperties = ['version' => $this->openApiVersion ?? OA\OpenApi::VERSION_3_1_0];
        if (isset($options['logger'])) {
            $contextProperties['logger'] = $options['logger'];
            unset($options['logger']);
        }
        $context = Util::createContext($contextProperties, null);

        $api = Util::createItem(OpenApi::class, $context);
        $api->openapi = $context->version;

        $isValidationRequired = true;
        if (isset($options['no-validation'])) {
            $isValidationRequired = !$options['no-validation'];
            unset($options['no-validation']);
        }

        $analysis = null;
        if ($isValidationRequired) {
            $analysis = new Analysis([$api], $context);
        }

        $this->describe($api, $options);

        $analysis?->validate();

        return $api;
    }

    private function describe(OA\OpenApi $api, array $options): void
    {
        $schemaStorage = new SchemaStorage();
        $modelStorage = new ModelStorage($this->modelDescribers, $schemaStorage);
        $parameterStorage = new ParameterStorage($this->dataTypeDescribeHelper);
        $headerStorage = new HeaderStorage($this->dataTypeDescribeHelper);
        $requestBodyStorage = new RequestBodyStorage();
        $responseStorage = new ResponseStorage();
        $errorResponseStorage = new ErrorResponseStorage();
        $this->dataTypeDescribeHelper->setSchemaStorage($schemaStorage);
        foreach ($this->modelDescribers as $modelDescriber) {
            if ($modelDescriber instanceof SchemaStorageAwareInterface) {
                $modelDescriber->setSchemaStorage($schemaStorage);
            }
            if ($modelDescriber instanceof ModelStorageAwareInterface) {
                $modelDescriber->setModelStorage($modelStorage);
            }
        }
        try {
            foreach ($this->describers as $describer) {
                $this->applyDescriber(
                    $api,
                    $describer,
                    $options,
                    $schemaStorage,
                    $modelStorage,
                    $parameterStorage,
                    $headerStorage,
                    $requestBodyStorage,
                    $responseStorage,
                    $errorResponseStorage
                );
            }
        } finally {
            $this->dataTypeDescribeHelper->setSchemaStorage(null);
            foreach ($this->modelDescribers as $modelDescriber) {
                if ($modelDescriber instanceof ResetInterface) {
                    $modelDescriber->reset();
                }
                if ($modelDescriber instanceof SchemaStorageAwareInterface) {
                    $modelDescriber->setSchemaStorage(null);
                }
                if ($modelDescriber instanceof ModelStorageAwareInterface) {
                    $modelDescriber->setModelStorage(null);
                }
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(ExcessiveParameterList)
     */
    private function applyDescriber(
        OA\OpenApi $api,
        DescriberInterface $describer,
        array $options,
        SchemaStorage $schemaStorage,
        ModelStorage $modelStorage,
        ParameterStorage $parameterStorage,
        HeaderStorage $headerStorage,
        RequestBodyStorage $requestBodyStorage,
        ResponseStorage $responseStorage,
        ErrorResponseStorage $errorResponseStorage
    ): void {
        if ($describer instanceof SchemaStorageAwareInterface) {
            $describer->setSchemaStorage($schemaStorage);
        }
        if ($describer instanceof ModelStorageAwareInterface) {
            $describer->setModelStorage($modelStorage);
        }
        if ($describer instanceof ParameterStorageAwareInterface) {
            $describer->setParameterStorage($parameterStorage);
        }
        if ($describer instanceof HeaderStorageAwareInterface) {
            $describer->setHeaderStorage($headerStorage);
        }
        if ($describer instanceof RequestBodyStorageAwareInterface) {
            $describer->setRequestBodyStorage($requestBodyStorage);
        }
        if ($describer instanceof ResponseStorageAwareInterface) {
            $describer->setResponseStorage($responseStorage);
        }
        if ($describer instanceof ErrorResponseStorageAwareInterface) {
            $describer->setErrorResponseStorage($errorResponseStorage);
        }
        try {
            $describer->describe($api, $options);
        } finally {
            if ($describer instanceof ResetInterface) {
                $describer->reset();
            }
            if ($describer instanceof SchemaStorageAwareInterface) {
                $describer->setSchemaStorage(null);
            }
            if ($describer instanceof ModelStorageAwareInterface) {
                $describer->setModelStorage(null);
            }
            if ($describer instanceof ParameterStorageAwareInterface) {
                $describer->setParameterStorage(null);
            }
            if ($describer instanceof HeaderStorageAwareInterface) {
                $describer->setHeaderStorage(null);
            }
            if ($describer instanceof RequestBodyStorageAwareInterface) {
                $describer->setRequestBodyStorage(null);
            }
            if ($describer instanceof ResponseStorageAwareInterface) {
                $describer->setResponseStorage(null);
            }
            if ($describer instanceof ErrorResponseStorageAwareInterface) {
                $describer->setErrorResponseStorage(null);
            }
        }
    }
}
