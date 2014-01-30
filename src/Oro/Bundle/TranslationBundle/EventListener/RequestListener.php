<?php

namespace Oro\Bundle\TranslationBundle\EventListener;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationResource;
use Oro\Bundle\TranslationBundle\Translation\DownloadableTranslationResource;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class RequestListener
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var DynamicTranslationMetadataCache
     */
    protected $databaseTranslationMetadataCache;

    /**
     * @var DynamicTranslationMetadataCache
     */
    protected $downloadTranslationMetadataCache;

    /**
     * @var string[]
     */
    protected $registeredTranslationResources;

    /**
     * Constructor
     *
     * @param Translator                      $translator
     * @param DynamicTranslationMetadataCache $databaseTranslationMetadataCache
     * @param DynamicTranslationMetadataCache $downloadTranslationMetadataCache
     */
    public function __construct(
        Translator $translator,
        DynamicTranslationMetadataCache $databaseTranslationMetadataCache,
        DynamicTranslationMetadataCache $downloadTranslationMetadataCache
    ) {
        $this->translator                       = $translator;
        $this->databaseTranslationMetadataCache = $databaseTranslationMetadataCache;
        $this->downloadTranslationMetadataCache = $downloadTranslationMetadataCache;
        $this->registeredTranslationResources   = [];
    }

    /**
     * Register a database translation resources for the current request locale
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
            $locale = $this->translator->getLocale();
            // check if a resource has already registered to avoid duplicates
            if (!isset($this->registeredTranslationResources[$locale])) {
                $this->translator->addResource(
                    'oro_database_translation',
                    new OrmTranslationResource($locale, $this->databaseTranslationMetadataCache),
                    $locale,
                    'messages'
                );
                $this->translator->addResource(
                    'oro_database_translation',
                    new DownloadableTranslationResource($locale, $this->downloadTranslationMetadataCache),
                    $locale,
                    'messages'
                );
                $this->registeredTranslationResources[$locale] = true;
            }
        }
    }
}
