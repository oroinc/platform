<?php

namespace Oro\Bundle\EmailBundle\Twig\EmailTemplateLoader;

use Oro\Bundle\EmailBundle\Model\EmailTemplate as EmailTemplateModel;
use Twig\Loader\LoaderInterface;

/**
 * Interface for email template TWIG template loader.
 */
interface EmailTemplateLoaderInterface extends LoaderInterface
{
    public function getEmailTemplate(string $name): EmailTemplateModel;
}
