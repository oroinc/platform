<form <?php echo $view['layout']->block($block, 'block_attributes') ?><?php if (isset($action)): ?> action="<?php echo $action ?>"<?php endif ?><?php if (isset($method)): ?> method="<?php echo strtolower($method) ?>"<?php endif ?><?php if (isset($enctype)): ?> enctype="<?php echo $enctype ?>"<?php endif ?>>
<?php if (isset($method) && $method !== 'GET' && $method !== 'POST'): ?>
    <input type="hidden" name="_method" value="<?php echo $method ?>" />
<?php endif ?>
