<?php echo $view->escape($translatable ? $view['translator']->trans($text, $text_parameters, $translation_domain) : strtr($text, $text_parameters)) ?>
