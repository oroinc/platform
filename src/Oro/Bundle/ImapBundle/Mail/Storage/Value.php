<?php

namespace Oro\Bundle\ImapBundle\Mail\Storage;

use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;

/**
 * Encapsulates an email header value with character encoding information.
 *
 * This class represents a single email header value (such as subject, sender, or recipient)
 * along with its character encoding. It provides methods to retrieve both the raw value
 * and the decoded value with proper character set conversion for correct display and processing.
 */
class Value
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $encoding;

    /**
     * @param string $value
     * @param string $encoding
     */
    public function __construct($value, $encoding)
    {
        $this->value = $value;
        $this->encoding = $encoding;
    }

    /**
     * Gets the value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Gets the decoded value
     *
     * @param string $toEncoding The type of encoding that the content is being converted to.
     *                           Defaults to 'UTF-8'
     * @return string
     */
    public function getDecodedValue($toEncoding = 'UTF-8')
    {
        return ContentDecoder::decode(
            $this->value,
            null,
            $this->encoding,
            $toEncoding
        );
    }

    /**
     * Gets the value encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }
}
