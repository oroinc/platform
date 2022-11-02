<?php

namespace Oro\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks if code have deprecation annotation.
 *
 * @category PHP
 */
class DeprecatedSniff implements Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     */
    public array $supportedTokenizers = [
        'PHP',
        'JS',
    ];

    /**
     * {@inheritDoc}
     */
    public function register()
    {
        return array_diff(Tokens::$commentTokens, Tokens::$phpcsCommentTokens);
    }

    /**
     * {@inheritDoc}
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$stackPtr]['content'];
        $matches = [];
        preg_match('/(?:\A|[^\p{L}]+)deprecated([^\p{L}]+(.*)|\Z)/ui', $content, $matches);
        if (empty($matches) === false) {
            // Clear whitespace and some common characters not required at
            // the end of a to-do message to make the warning more informative.
            $type        = 'CommentFound';
            $todoMessage = trim($matches[1]);
            $todoMessage = trim($todoMessage, '-:[](). ');
            $error       = 'The deprecations is not allowed in master branch.';
            $data        = [$todoMessage];
            if ($todoMessage !== '') {
                $type   = 'DeprecatedFound';
                $error .= ' "%s"';
            }

            $phpcsFile->addWarning($error, $stackPtr, $type, $data);
        }
    }
}
