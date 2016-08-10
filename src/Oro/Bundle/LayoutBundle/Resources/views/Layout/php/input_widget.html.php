<?php //@codingStandardsIgnoreFile ?>
<?php if (isset($attr['placeholder'])) $attr['placeholder'] = $view['layout']->text($attr['placeholder'], $translation_domain); ?>
<input <?php echo $view['layout']->block($block, 'block_attributes') ?><?php if (isset($type)): ?> type="<?php echo $type ?>"<?php endif ?><?php if (isset($name)): ?> name="<?php echo $name ?>"<?php endif ?><?php if (isset($id)): ?> id="<?php echo $id ?>"<?php endif ?>/>
