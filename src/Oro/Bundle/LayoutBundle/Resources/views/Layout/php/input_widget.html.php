<?php //@codingStandardsIgnoreFile ?>
<?php if (!empty($id)) $attr['id'] = $id; ?>
<?php if (!empty($type)) $attr['type'] = $type; ?>
<?php if (!empty($name)) $attr['name'] = $name; ?>
<?php if (!empty($attr['placeholder'])) $attr['placeholder'] = $view['layout']->text($attr['placeholder'], $translation_domain); ?>
<input <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>/>
