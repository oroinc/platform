<?php //@codingStandardsIgnoreFile?>
<?php $attr = array_merge($form->vars['attr'], $attr) ?>
<?php if (!isset($attr['id'])) {
    $attr['id'] = $form->vars['id'];
} ?>

<?php $options = ['attr' => $attr] ?>

<?php $action = isset($form_action) ? $form_action : (isset($form_route_name) ? $view['router']->generate($form_route_name, $form_route_parameters) : null) ?>
<?php if ($action !== null) {
    $options = array_merge($options, ['action' => $action]);
} ?>

<?php if (isset($form_method) && !in_array($form_method, ['GET', 'POST'])) {
    $form_method = 'POST';
} ?>

<?php if (isset($form_multipart)) {
    $options = array_merge($options, ['multipart' => $form_multipart]);
} ?>

<?php echo $view['form']->start($form, $options) ?>

<?php if (isset($form_method)): ?>
    <input type="hidden" name="_method" value="<?php echo $form_method ?>" />
<?php endif ?>
