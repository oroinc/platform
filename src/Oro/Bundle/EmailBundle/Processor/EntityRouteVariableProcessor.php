<?php

namespace Oro\Bundle\EmailBundle\Processor;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EmailBundle\Provider\UrlProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateData;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariableProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * Processes entity route variables.
 */
class EntityRouteVariableProcessor implements VariableProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var UrlProvider */
    private $urlProvider;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        UrlProvider $urlProvider,
        LoggerInterface $logger
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->urlProvider = $urlProvider;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(string $variable, array $processorArguments, TemplateData $data): void
    {
        $data->setComputedVariable($variable, $this->getUrl($processorArguments['route'], $variable, $data));
    }

    private function getUrl(string $routeName, string $variable, TemplateData $data): ?string
    {
        $entity = $data->getEntityVariable($data->getParentVariablePath($variable));

        $url = null;
        try {
            $params = [];
            if (!preg_match('/^.*(_index|_create)$/', $routeName)) {
                $params['id'] = $this->doctrineHelper->getSingleEntityIdentifier($entity);
            }
            $url = $this->urlProvider->getAbsoluteUrl($routeName, $params);
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('The variable "%s" cannot be resolved.', $variable),
                [
                    'exception' => $e,
                    'entity'    => is_object($entity) ? ClassUtils::getClass($entity) : gettype($entity)
                ]
            );
        }

        return $url;
    }
}
