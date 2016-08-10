<?php //@codingStandardsIgnoreFile ?>
<link <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?><?php if (isset($type)): ?> type="<?php echo $type ?>"<?php endif ?> rel="<?php echo $rel ?>" href="<?php echo $href ?>"/>
