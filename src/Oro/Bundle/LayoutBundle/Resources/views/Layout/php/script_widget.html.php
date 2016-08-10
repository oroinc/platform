<?php //@codingStandardsIgnoreFile ?>
<?php if (!empty($src)): ?>
    <script <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>> type="<?php echo $type ?>"<?php if (isset($src)): ?> src="<?php echo $src ?>"<?php endif ?><?php if ($async == 'true'): ?> async="async"<?php endif ?><?php if ($defer == 'true'): ?> defer="defer"<?php endif ?><?php if (isset($crossorigin)): ?> crossorigin="<?php echo $crossorigin ?>"<?php endif ?></script>
<?php else: ?>
    <script <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?>  type="<?php echo $type ?>"<?php if ($async == 'true'): ?> async="async"<?php endif ?><?php if ($defer == 'true'): ?> defer="defer"<?php endif ?><?php if (isset($crossorigin)): ?> crossorigin="<?php echo $crossorigin ?>"<?php endif ?>>
        <?php echo $content ?>
    </script>
<?php endif ?>
