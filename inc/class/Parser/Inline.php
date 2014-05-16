<?php
/*
 * Copyright (C) 2013, 2014 Frank Usrs
 *
 * See LICENSE for terms and conditions of use.
 */

/**
 * @author Stee
 * @todo Document this.
 */
abstract class Parser_Inline extends Parser {
    protected $stack = array();
    protected $tree;

    protected $markup = array();
    protected $modifiers = array();
    
    public function __construct($text, Array $modifiers = array()) {
        $this->modifiers = $modifiers;

        $this->raw = $text;

        $this->parse();
    }

    protected function parse() {
        foreach ($this->markup as &$markup) {
            if (!isset($markup['close_token'])) {
                $markup['close_token'] = $markup['token'];
            }
        }

        $this->makeStack();
        $this->makeTree();

        $this->parsed = $this->formatTree($this->tree);
    }

    protected function sortByLength($a, $b){
        return strlen($b) - strlen($a);
    }

    protected function makeStack() {
        $this->stack = array();

        $regex = array();

        // TODO: We only need to run the code from here and until the preg_split
        // once per session, but it gets done every time we invoke an inline
        // parser. Waste of resources.
        foreach ($this->markup as $markup) {
            for ($i = 0; $i < count($markup['token']); $i++) {
                $regex[] = str_replace('\+', '+', preg_quote($markup['token'][$i], '/'));

                if ($markup['close_token'][$i] !== $markup['token'][$i]) {
                    $regex[] = str_replace('\+', '+', preg_quote($markup['close_token'][$i], '/'));
                }
            }
        }

        usort($regex, array($this, 'sortByLength'));

        $regex = '/('.implode('|', $regex).')/';

        $stack = preg_split($regex, $this->raw, -1, PREG_SPLIT_DELIM_CAPTURE);

        $new_stack = array();
        $temp_stack = false;

        foreach ($stack as $part) {
            if ($temp_stack !== false) {
                $temp_stack .= $part;

                if (strpos($part, ' ') !== false) {
                    // Space found. Stop treating everything literally.
                    $new_stack[] = $temp_stack;
                    $temp_stack = false;
                }
            } elseif (preg_match('@https?://@', $part)) {
                // URL found. Treat everything literally until we find whitespace.
                $temp_stack = '';
                $temp_stack .= $part;
            } else {
                $new_stack[] = $part;
            }
        }

        if ($temp_stack !== false) {
            $new_stack[] = $temp_stack;
        }

        $this->stack = array_filter($new_stack, 'strlen');
    }

    protected function isToken($tokens, $part) {
        foreach ($tokens as $i => $token) {
            if (strlen($token) > 1 && substr($token, -1) == '+') {
                if (str_repeat(substr($token, 0, -1), strlen($part)) === $part) {
                    return $i;
                }
            } elseif ($token === $part) {
                return $i;
            }
        }

        return false;
    }

    protected function isOpen($nest, $token, $check_other_tokens = false) {
        foreach ($nest as $markup) {
            if (!$check_other_tokens) {
                if ($markup['close_token'] === $token) {
                    return true;
                }
            } elseif ($this->isToken($markup['token'], $token) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @todo Maybe add some sort of sane debugging stuff for this?
     * @todo '*_foo_*' results in a fatal error.
     * @todo '*foo_' kills the underscore.
     */
    protected function makeTree() {
        $this->tree = new Parser_Inline_Tree();

        $current = $this->tree;
        $nest = array();

        foreach ($this->stack as $part) {
            $this_markup = false;

            foreach ($this->markup as $markup) {
                $token_num = $this->isToken($markup['token'], $part);

                if ($token_num !== false) {
                    $this_markup = $markup;
                    $this_markup['open_token'] = $markup['token'][$token_num];
                    $this_markup['real_open'] = $part;
                    $this_markup['close_token'] = $markup['close_token'][$token_num];
                    $this_markup['state'] = 'open';
                }

                $token_num = $this->isToken($markup['close_token'], $part);

                if ($token_num !== false) {
                    $unknown_state = isset($this_markup['state']);

                    $this_markup = $markup;
                    $this_markup['open_token'] = $markup['token'][$token_num];
                    $this_markup['close_token'] = $markup['close_token'][$token_num];

                    if ($unknown_state) {
                        if ($this->isOpen($nest, $part)) {
                            $this_markup['state'] = 'close';
                        } else {
                            $this_markup['real_open'] = $part;
                            $this_markup['state'] = 'open';
                        }
                    } else {
                        $this_markup['state'] = 'close';
                    }
                }

                if ($this_markup !== false) {
                    break;
                }
            }

            if ($this_markup !== false) {
                $nest_current = end($nest);

                if ($this_markup['state'] == 'close') {
                    if ($nest_current && $nest_current['close_token'] === $part) {
                        // Correct nesting.
                        // Move back up a layer.
                        array_pop($nest);

                        $current = $current->parent;
                    } elseif ($this->isOpen($nest, $part)) {
                        // Invalid. You're closing a tag out of order.
                        for ($x = count($nest); $x--;) {
                            $markup = array_pop($nest);

                            if ($markup['close_token'] === $part) {
                                break;
                            }

                            $defunct = $current;

                            // Go back up a layer.
                            $current = $defunct->parent;

                            // Remove the invalid layer from the tree.
                            $current->pop();

                            // Move the contents of the invalid layer into its parent.
                            $current->add($markup['real_open']);
                            $defunct->copyTo($current);
                        }

                        // Go back up a step again.
                        $current = $current->parent;
                    } else {
                        // Close tag out of nowhere.
                        $current->add($part);
                    }
                } elseif ($this_markup['state'] == 'open') {
                    if ($this_markup['open_token'] === $this_markup['close_token']) {
                        $this_markup['close_token'] = $part;
                    }

                    if (
                        $nest_current &&
                        !$nest_current['children'] &&
                        $nest_current['close_token'] !== $part
                    ) {
                        $current->add($part);
                    } elseif ($this->isOpen($nest, $part, true)) {
                        // Already open, but with another token, e.g. "**"
                        // instead of "__". Treat literally.
                        $current->add($part);
                    } else {
                        // Starting a new layer.
                        $newNode = new Parser_Inline_Tree($current, $this_markup);
                        $current->add($newNode);

                        $current = $newNode;
                        $nest[] = $this_markup;
                    }
                }
            } else {
                // Add plain text.
                $current->add($part);
            }
        }

        // If $nest is not empty at this point, then something was not closed.
        for ($i = count($nest); $i--;) {
            $defunct = $current;
            $current = $defunct->parent;

            $current->pop();
            $current->add($nest[$i]['real_open']);
            $defunct->copyTo($current);
        }
    }
    
    protected function doLinks($text, $callback_for_the_bits_outside) {
        $offset = 0;
        $output = '';
        
        // Find all links
        preg_match_all(
            '@((?:https?://|ftp://|irc://)[^\s<>()"]+?(?:\([^\s<>()"]*?\)[^\s<>()"]*?)*)((?:\s|<|>|"|\.||\]|!|\?|,)*(?:[\s<>()"]|$))@u',
            $text,
            $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            // Preceding text
            $before = substr($text, $offset, $match[0][1] - $offset);
        
            // Following text
            $after = $match[2][0];
        
            // Apply markup to the parts outside the URL
            if ($callback_for_the_bits_outside !== false) {
                $before = call_user_func($callback_for_the_bits_outside, $before);
                $after = call_user_func($callback_for_the_bits_outside, $after);
            }
        
            // Build and append link
            $output .= sprintf('%s<a href="%s" rel="nofollow">%s</a>%s',
                $before,
                $this->escape($match[1][0]),
                $this->escape($match[1][0]),
                $after
            );
        
            // Increment offset
            $offset = $match[0][1] + strlen($match[0][0]);
        }
        
        unset($before, $after, $match);
        
        // Do the very last bit
        $end = substr($text, $offset);

        if ($callback_for_the_bits_outside !== false) {
            $end = call_user_func($callback_for_the_bits_outside, $end);
        }

        $output .= $end;
        
        return $output;
    }

    protected function doText($text) {
        $text = $this->escape($text);
        
        foreach ($this->modifiers as $modifier) {
            // the modifier takes escaped text as a reference
            call_user_func_array($modifier, array(&$text));
        }

        return $text;
    }

    protected function formatText($text) {
        return $this->doLinks($text, array($this, 'doText'));
    }

    protected function formatTree($tree) {
        $text = '';

        foreach ($tree->nodes as $node) {
            if ($node instanceof Parser_Inline_Tree) {
                $text .= $this->formatTree($node);
            } else {
                // text node
                $this->stripped .= $node;
                $text .= $this->formatText($node);
            }
        }

        if ($text === '') {
            // Empty. Example: "****". Treat literally?
            return $tree->markup['open_token'].$tree->markup['close_token'];
        }

        if ($tree->markup) {
            if (isset($tree->markup['callback'])) {
                $text = call_user_func($tree->markup['callback'], $text);
            }

            return $tree->markup['format'][0].$text.$tree->markup['format'][1];
        }

        return $text;
    }
}

/* vim: set ts=4 sw=4 sts=4 et: */
