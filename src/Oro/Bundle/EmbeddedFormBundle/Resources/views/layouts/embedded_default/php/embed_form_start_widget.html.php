<?php //@codingStandardsIgnoreFile ?>
<?php $attr = array_merge($form->vars['attr'], $attr) ?>
<?php $action = isset($action_path) ? $action_path : (isset($action_route_name) ? $view['router']->generate($action_route_name, $action_route_parameters) : null) ?>
<?php if (isset($method) && $method !== 'GET' && $method !== 'POST') $form_method = 'POST'; ?>
<form <?php echo $view['layout']->block($block, 'block_attributes', ['attr' => $attr]) ?><?php if (isset($action)): ?> action="<?php echo $action ?>"<?php endif ?><?php if (isset($method)): ?> method="<?php echo strtolower(isset($form_method) ? $form_method : $method) ?>"<?php endif ?><?php if (isset($enctype)): ?> enctype="<?php echo $enctype ?>"<?php endif ?>>
<?php if (isset($form_method)): ?>
    <input type="hidden" name="_method" value="<?php echo $method ?>" />
<?php endif ?>
