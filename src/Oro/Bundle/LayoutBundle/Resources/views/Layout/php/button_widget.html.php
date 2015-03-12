<?php if ($element === 'input'): ?>
<input <?php echo $view['layout']->block($block, 'block_attributes') ?><?php if (isset($name)): ?> name="<?php echo $name ?>"<?php endif ?><?php if (isset($value) || isset($text)): ?> value="<?php echo $view->escape(isset($value) ? $value : $view['layout']->text($text, $translation_domain)) ?>"<?php endif ?>/>
<?php else: ?>
<button <?php echo $view['layout']->block($block, 'block_attributes') ?><?php if (isset($name)): ?> name="<?php echo $name ?>"<?php endif ?><?php if (isset($value)): ?> value="<?php echo $view->escape($value) ?>"<?php endif ?>><?php if (isset($text)): ?><?php echo $view->escape($view['layout']->text($text, $translation_domain)) ?><?php endif ?><?php echo $view['layout']->widget($block) ?></button>
<?php endif ?>
