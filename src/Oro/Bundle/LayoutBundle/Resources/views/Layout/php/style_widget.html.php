<?php //@codingStandardsIgnoreFile ?>
<?php if (!empty($src)): ?>
    <link rel="stylesheet" <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?> type="<?php echo $type ?>"<?php if (isset($src)): ?> href="<?php echo $src ?>"<?php endif ?><?php if (isset($media)): ?> media="<?php echo $media ?>"<?php endif ?><?php if ($scoped == 'true'): ?> scoped="scoped"<?php endif ?><?php if (isset($crossorigin)): ?> crossorigin="<?php echo $crossorigin ?>"<?php endif ?>/>
<?php else: ?>
    <style <?php echo $view['layout']->block($block, 'block_attributes', array('attr' => $attr)) ?><?php if (isset($type)): ?> type="<?php echo $type ?>"<?php endif ?><?php if (isset($media)): ?> media="<?php echo $media ?>"<?php endif ?><?php if ($scoped == 'true'): ?> scoped="scoped"<?php endif ?><?php if (isset($crossorigin)): ?> crossorigin="<?php echo $crossorigin ?>"<?php endif ?>>
        <?php echo $content ?>
    </style>
<?php endif ?>
