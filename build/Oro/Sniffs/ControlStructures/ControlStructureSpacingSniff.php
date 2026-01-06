<?php

declare(strict_types=1);

namespace Oro\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Standards\PSR12;
use PHP_CodeSniffer\Standards\PSR2;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks that control structures have the correct spacing.
 *
 * This is similar to {@see PSR12\Sniffs\ControlStructures\ControlStructureSpacingSniff},
 * with the following exception: when the entire condition is a single multi-line call-like construct
 * (function call, method call, or callable invocation), the first expression may be on the same line
 * as the opening parenthesis, and the closing `)` may be on the same line as the call's closing `)`.
 *
 * Allowed examples:
 *
 * 1) Multi-line method call as the entire condition:
 *
 *
 *      // GOOD
 *      if ($this->someMethod(
 *          $param1,
 *          $param2
 *      )) {
 *          // ...
 *      }
 *
 * 2) Multi-line function call as the entire condition:
 *
 *
 *      // GOOD
 *      if (someFunc(
 *          $a,
 *          $b
 *      )) {
 *          // ...
 *      }
 *
 * 3) Multi-line callable invocation as the entire condition:
 *
 *
 *      // GOOD
 *      if (($factory)(
 *          $a,
 *          $b
 *      )) {
 *          // ...
 *      }
 *
 * Violation examples:
 *
 * A) First expression not on the line after opening parenthesis (not a single call-like construct):
 *
 *
 *      // BAD
 *      if ($foo
 *          && $bar
 *      ) {
 *          // ...
 *      }
 *
 * B) Grouped boolean expression (NOT a single call-like construct), even if it ends with `))`:
 *
 *
 *      // BAD
 *      if ($foo
 *          && ($bar
 *              || $baz
 *          )) {
 *          // ...
 *      }
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ControlStructureSpacingSniff implements Sniff
{
    /** The number of spaces code should be indented. */
    public int $indent = 4;

    private PSR2\Sniffs\ControlStructures\ControlStructureSpacingSniff $psr2ControlStructureSpacing;

    public function __construct()
    {
        $this->psr2ControlStructureSpacing = new PSR2\Sniffs\ControlStructures\ControlStructureSpacingSniff();
    }

    /**
     * @return array<int|string>
     */
    public function register(): array
    {
        return [
            T_IF,
            T_WHILE,
            T_FOREACH,
            T_FOR,
            T_SWITCH,
            T_ELSEIF,
            T_CATCH,
            T_MATCH,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if (
            isset($tokens[$stackPtr]['parenthesis_opener']) === false
            || isset($tokens[$stackPtr]['parenthesis_closer']) === false
        ) {
            return;
        }

        $parenOpener = $tokens[$stackPtr]['parenthesis_opener'];
        $parenCloser = $tokens[$stackPtr]['parenthesis_closer'];

        if ($tokens[$parenOpener]['line'] === $tokens[$parenCloser]['line']) {
            // Conditions are all on the same line, so follow PSR2.
            $this->psr2ControlStructureSpacing->process($phpcsFile, $stackPtr);
            return;
        }

        $next = $phpcsFile->findNext(T_WHITESPACE, ($parenOpener + 1), $parenCloser, true);
        if ($next === false) {
            // No conditions; parse error.
            return;
        }

        // Check the first expression.
        $allowSameLineOpen = $this->shouldAllowSameLineFirstExpression($phpcsFile, $parenOpener, $parenCloser, $next);

        if (
            $allowSameLineOpen === false
            && $tokens[$next]['line'] !== ($tokens[$parenOpener]['line'] + 1)
        ) {
            $error = 'The first expression of a multi-line control structure'
                . ' must be on the line after the opening parenthesis';
            $fix = $phpcsFile->addFixableError($error, $next, 'FirstExpressionLine');
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                if ($tokens[$next]['line'] > ($tokens[$parenOpener]['line'] + 1)) {
                    for ($i = ($parenOpener + 1); $i < $next; $i++) {
                        if ($tokens[$next]['line'] === $tokens[$i]['line']) {
                            break;
                        }

                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                }

                $phpcsFile->fixer->addNewline($parenOpener);
                $phpcsFile->fixer->endChangeset();
            }
        }

        // Check the indent of each line.
        $first = $phpcsFile->findFirstOnLine(T_WHITESPACE, $stackPtr, true);
        $requiredIndent = ($tokens[$first]['column'] + $this->indent - 1);
        for ($i = $parenOpener; $i < $parenCloser; $i++) {
            if (
                $tokens[$i]['column'] !== 1
                || $tokens[($i + 1)]['line'] > $tokens[$i]['line']
                || isset(Tokens::COMMENT_TOKENS[$tokens[$i]['code']]) === true
            ) {
                continue;
            }

            if (($i + 1) === $parenCloser) {
                break;
            }

            // Leave indentation inside multi-line strings.
            if (
                isset(Tokens::TEXT_STRING_TOKENS[$tokens[$i]['code']]) === true
                || isset(Tokens::HEREDOC_TOKENS[$tokens[$i]['code']]) === true
            ) {
                continue;
            }

            if ($tokens[$i]['code'] !== T_WHITESPACE) {
                $foundIndent = 0;
            } else {
                $foundIndent = $tokens[$i]['length'];
            }

            if ($foundIndent < $requiredIndent) {
                $error = 'Each line in a multi-line control structure must be indented at least once;'
                    . ' expected at least %s spaces, but found %s';
                $data = [$requiredIndent, $foundIndent];
                $fix = $phpcsFile->addFixableError($error, $i, 'LineIndent', $data);
                if ($fix === true) {
                    $padding = \str_repeat(' ', $requiredIndent);
                    if ($foundIndent === 0) {
                        $phpcsFile->fixer->addContentBefore($i, $padding);
                    } else {
                        $phpcsFile->fixer->replaceToken($i, $padding);
                    }
                }
            }
        }

        // Check the closing parenthesis.
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($parenCloser - 1), $parenOpener, true);

        $allowSameLineClose = $this->shouldAllowSameLineControlCloser($phpcsFile, $parenOpener, $parenCloser, $prev);

        if (
            $allowSameLineClose === false
            && $tokens[$parenCloser]['line'] !== ($tokens[$prev]['line'] + 1)
        ) {
            $error = 'The closing parenthesis of a multi-line control structure'
                . ' must be on the line after the last expression';
            $fix = $phpcsFile->addFixableError($error, $parenCloser, 'CloseParenthesisLine');
            if ($fix === true) {
                if ($tokens[$parenCloser]['line'] === $tokens[$prev]['line']) {
                    $phpcsFile->fixer->addNewlineBefore($parenCloser);
                } else {
                    $phpcsFile->fixer->beginChangeset();
                    for ($i = ($prev + 1); $i < $parenCloser; $i++) {
                        // Maintain existing newline.
                        if ($tokens[$i]['line'] === $tokens[$prev]['line']) {
                            continue;
                        }

                        // Maintain existing indent.
                        if ($tokens[$i]['line'] === $tokens[$parenCloser]['line']) {
                            break;
                        }

                        $phpcsFile->fixer->replaceToken($i, '');
                    }

                    $phpcsFile->fixer->endChangeset();
                }
            }
        }

        if ($tokens[$parenCloser]['line'] !== $tokens[$prev]['line']) {
            $requiredIndent = ($tokens[$first]['column'] - 1);
            $foundIndent = ($tokens[$parenCloser]['column'] - 1);
            if ($foundIndent !== $requiredIndent) {
                $error = 'The closing parenthesis of a multi-line control structure'
                    . ' must be indented to the same level as start of the control structure;'
                    . ' expected %s spaces but found %s';
                $data = [
                    $requiredIndent,
                    $foundIndent,
                ];
                $fix = $phpcsFile->addFixableError($error, $parenCloser, 'CloseParenthesisIndent', $data);
                if ($fix === true) {
                    $padding = \str_repeat(' ', $requiredIndent);
                    if ($foundIndent === 0) {
                        $phpcsFile->fixer->addContentBefore($parenCloser, $padding);
                    } else {
                        $phpcsFile->fixer->replaceToken(($parenCloser - 1), $padding);
                    }
                }
            }
        }
    }

    /**
     * Decide whether to allow the first expression to be on the same line as the opening parenthesis.
     *
     * The only allowed case is:
     * - the first expression is a call-like construct (function/method call, callable invocation, etc.),
     * - the call-like construct starts on the same line as the control opener,
     * - and the call-like construct spans multiple lines.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function shouldAllowSameLineFirstExpression(
        File $phpcsFile,
        int $parenOpener,
        int $parenCloser,
        int $next
    ): bool {
        $tokens = $phpcsFile->getTokens();

        // We only consider relaxing when the first expression is on the same line as the opening parenthesis.
        if ($tokens[$next]['line'] !== $tokens[$parenOpener]['line']) {
            return false;
        }

        // Find the last non-whitespace token before the control closer.
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($parenCloser - 1), $parenOpener, true);
        if ($prev === false) {
            return false;
        }

        // The last token must be a closing parenthesis.
        if ($tokens[$prev]['code'] !== T_CLOSE_PARENTHESIS) {
            return false;
        }

        // Get the opener of this closing parenthesis.
        $callOpener = $tokens[$prev]['parenthesis_opener'] ?? null;
        if (\is_int($callOpener) === false) {
            return false;
        }

        // The call opener must be inside the control-structure parentheses.
        if ($callOpener <= $parenOpener || $callOpener >= $parenCloser) {
            return false;
        }

        // The call opener must be on the same line as the control opener.
        // This ensures the entire condition is a single call-like construct.
        if ($tokens[$callOpener]['line'] !== $tokens[$parenOpener]['line']) {
            return false;
        }

        // Check if this is a call-like construct.
        if ($this->isCallLikeOpenParen($phpcsFile, $callOpener) === false) {
            return false;
        }

        // The call-like construct must span multiple lines.
        if ($tokens[$callOpener]['line'] >= $tokens[$prev]['line']) {
            return false;
        }

        return true;
    }

    /**
     * Decide whether to allow the control-structure closer `)` to be on the same line as the last expression.
     *
     * The only allowed case is:
     * - the token immediately before the control closer is a nested `)` (no non-whitespace in between),
     * - that nested `)` closes a call-like construct opened inside the control-structure parentheses,
     * - the call-like construct starts on the same line as the control opener,
     * - and the nested parentheses span multiple lines (to avoid exempting redundant single-line grouping).
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function shouldAllowSameLineControlCloser(
        File $phpcsFile,
        int $parenOpener,
        int $parenCloser,
        int $prev
    ): bool {
        $tokens = $phpcsFile->getTokens();

        // We only consider relaxing when the control closer is on the same line as the last non-whitespace token.
        if ($tokens[$parenCloser]['line'] !== $tokens[$prev]['line']) {
            return false;
        }

        // We only relax for the common and safe case: `))` where $prev is the nested close paren.
        if ($tokens[$prev]['code'] !== T_CLOSE_PARENTHESIS) {
            return false;
        }

        // Ensure there is nothing but whitespace/comments between the nested `)` and the control `)`.
        // $prev is already "previous non-whitespace", so between($prev, $parenCloser) must be whitespace-only.
        $between = $phpcsFile->findNext(T_WHITESPACE, ($prev + 1), $parenCloser, true);
        if ($between !== false) {
            return false;
        }

        // Determine the opener of the nested parentheses.
        $innerOpener = $tokens[$prev]['parenthesis_opener'] ?? null;
        if (\is_int($innerOpener) === false) {
            return false;
        }

        // Nested opener must be inside the control-structure parentheses.
        if ($innerOpener <= $parenOpener || $innerOpener >= $parenCloser) {
            return false;
        }

        // The call opener must be on the same line as the control opener.
        // This ensures the entire condition is a single call-like construct.
        if ($tokens[$innerOpener]['line'] !== $tokens[$parenOpener]['line']) {
            return false;
        }

        // Only relax for multi-line nested constructs.
        if ($tokens[$innerOpener]['line'] >= $tokens[$prev]['line']) {
            return false;
        }

        // Only relax when the nested parentheses look call-like, not a grouped boolean expression.
        if ($this->isCallLikeOpenParen($phpcsFile, $innerOpener) === false) {
            return false;
        }

        return true;
    }

    /**
     * Heuristic to determine whether an opening `(` is call-like (function/method call, callable invocation, etc.)
     * as opposed to grouping for boolean/arithmetic expressions.
     */
    private function isCallLikeOpenParen(File $phpcsFile, int $innerOpener): bool
    {
        $tokens = $phpcsFile->getTokens();

        $before = $phpcsFile->findPrevious(
            Tokens::EMPTY_TOKENS,
            $innerOpener - 1,
            null,
            true
        );

        if ($before === false) {
            return false;
        }

        $code = $tokens[$before]['code'];

        // Common call-like patterns:
        // - foo( ... )            => T_STRING before '('
        // - $fn( ... )            => T_VARIABLE before '('
        // - ($factory)( ... )     => T_CLOSE_PARENTHESIS before '(' (callable returned from expression)
        // - $obj->method( ... )   => T_STRING (method name) before '('; object operator is earlier
        // - Class::method( ... )  => T_STRING (method name) before '('; double colon is earlier
        // - $arr['k']( ... )      => T_CLOSE_SQUARE_BRACKET before '(' (array deref call)
        $callLikePredecessors = [
            T_STRING,
            T_VARIABLE,
            T_CLOSE_PARENTHESIS,
            T_CLOSE_SQUARE_BRACKET,
        ];

        if (\in_array($code, $callLikePredecessors, true)) {
            return true;
        }

        // Do not treat `&& (` / `|| (` / `and (` / `or (` / `! (` etc. as call-like.
        // In practice, most grouped boolean expressions will land here and be rejected.
        return false;
    }
}
