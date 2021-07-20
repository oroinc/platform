<?php

namespace Oro\Bundle\AttachmentBundle\Checker\Voter;

use Oro\Bundle\AttachmentBundle\ProcessorHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Checks whether libraries are present in the system.
 */
class PostProcessorsVoter implements VoterInterface
{
    public const ATTACHMENT_POST_PROCESSORS = 'attachment_post_processors';

    /**
     * @var null|string
     */
    private $jpegopim;

    /**
     * @var null|string
     */
    private $pngQuant;

    public function __construct(?string $jpegopim, ?string $pngQuant)
    {
        $this->jpegopim = $jpegopim;
        $this->pngQuant = $pngQuant;
    }

    /**
     * @inhericDoc
     */
    public function vote($feature, $scopeIdentifier = null): int
    {
        if ($feature === self::ATTACHMENT_POST_PROCESSORS) {
            $processorHelper = new ProcessorHelper($this->getParameters());
            try {
                $librariesExists = $processorHelper->librariesExists();
            } catch (\Exception $exception) {
                return self::FEATURE_DISABLED;
            }

            return $librariesExists ? self::FEATURE_ENABLED : self::FEATURE_DISABLED;
        }

        return self::FEATURE_ABSTAIN;
    }

    private function getParameters(): ParameterBag
    {
        return new ParameterBag([
            'liip_imagine.jpegoptim.binary' => $this->jpegopim,
            'liip_imagine.pngquant.binary' => $this->pngQuant
        ]);
    }
}
