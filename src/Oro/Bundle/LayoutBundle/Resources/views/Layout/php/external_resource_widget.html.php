<?php //@codingStandardsIgnoreFile ?>
<?php if(isset($type)) $attr['type'] = $type; ?>
<?php if(isset($rel)) $attr['rel'] = $rel; ?>
<?php if(isset($href)) $attr['href'] = $href; ?>
<link <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>/>
