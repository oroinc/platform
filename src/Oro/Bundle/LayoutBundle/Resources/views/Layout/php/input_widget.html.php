<?php //@codingStandardsIgnoreFile ?>
<?php if (isset($attr['placeholder'])) $attr['placeholder'] = $view['layout']->text($attr['placeholder'], $translation_domain); ?>
<input <?php echo $view['layout']->block($block, 'block_attributes') ?>/>
