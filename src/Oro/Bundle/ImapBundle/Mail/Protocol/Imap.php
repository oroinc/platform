<?php

namespace Oro\Bundle\ImapBundle\Mail\Protocol;

use Zend\Mail\Storage\Exception as BaseException;

use Oro\Bundle\ImapBundle\Mail\Protocol\Exception\InvalidEmailFormatException;

/**
 * Class Imap
 * Add PEEK capability to Zend Imap Protocol
 *
 * @package Oro\Bundle\ImapBundle\Mail\Protocol
 */
class Imap extends \Zend\Mail\Protocol\Imap
{
    /**
     * {@inheridoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function fetch($items, $from, $to = null)
    {
        if (is_array($from)) {
            $set = implode(',', $from);
        } elseif ($to === null) {
            $set = (int) $from;
        } elseif ($to === INF) {
            $set = (int) $from . ':*';
        } else {
            $set = (int) $from . ':' . (int) $to;
        }

        $items = (array) $items;
        $itemList = $this->escapeList($items);
        $item = str_replace('.PEEK', '', $items[0]);

        $tag = null;  // define $tag variable before first use
        $this->sendRequest('FETCH', array($set, $itemList), $tag);

        $result = array();
        $tokens = null; // define $tokens variable before first use
        while (!$this->readLine($tokens, $tag)) {
            // ignore other responses
            if ($tokens[1] != 'FETCH') {
                continue;
            }
            // ignore other messages
            if ($to === null && !is_array($from) && $tokens[0] != $from) {
                continue;
            }
            // if we only want one item we return that one directly
            if (count($items) == 1) {
                if ($tokens[2][0] == $item) {
                    $data = $tokens[2][1];
                } else {
                    // maybe the server send an other field we didn't wanted
                    $count = count($tokens[2]);
                    // we start with 2, because 0 was already checked
                    for ($i = 2; $i < $count; $i += 2) {
                        if ($tokens[2][$i] != $item) {
                            continue;
                        }
                        $data = $tokens[2][$i + 1];
                        break;
                    }
                }
            } else {
                $data = array();
                while (key($tokens[2]) !== null) {
                    $data[current($tokens[2])] = next($tokens[2]);
                    next($tokens[2]);
                }
            }
            // if we want only one message we can ignore everything else and just return
            if ($to === null && !is_array($from) && $tokens[0] == $from) {
                // we still need to read all liness
                while (!$this->readLine($tokens, $tag)) {
                }
                return $data;
            }
            $result[$tokens[0]] = $data;
        }

        if ($to === null && !is_array($from)) {
            if ($tokens[0] === 'OK' && $tokens[1] === 'FETCH' && $tokens[2] === 'completed.') {
                throw new InvalidEmailFormatException('Invalid email format');
            }
            throw new BaseException\RuntimeException('the single id was not found in response');
        }

        return $result;
    }
}
