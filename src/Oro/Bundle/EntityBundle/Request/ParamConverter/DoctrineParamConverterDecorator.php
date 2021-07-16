<?php

namespace Oro\Bundle\EntityBundle\Request\ParamConverter;

use Doctrine\DBAL\Exception\DriverException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Catch out of range exception to prevent 500 error on http request.
 */
class DoctrineParamConverterDecorator implements ParamConverterInterface
{
    private const SQL_STATE_NUMERIC_VALUE_OUT_OF_RANGE = '22003';

    /** @var ParamConverterInterface */
    private $paramConverter;

    public function __construct(ParamConverterInterface $paramConverter)
    {
        $this->paramConverter = $paramConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        try {
            return $this->paramConverter->apply($request, $configuration);
        } catch (DriverException $e) {
            return $this->handleDriverException($e, $configuration);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration)
    {
        return $this->paramConverter->supports($configuration);
    }

    /**
     * @throws DriverException
     */
    private function handleDriverException(DriverException $exception, ParamConverter $configuration)
    {
        // handle the situation when we try to get the entity with id that the database doesn't support
        if ($exception->getSQLState() === self::SQL_STATE_NUMERIC_VALUE_OUT_OF_RANGE) {
            throw new NotFoundHttpException(sprintf('%s object not found.', $configuration->getClass()));
        }

        throw $exception;
    }
}
