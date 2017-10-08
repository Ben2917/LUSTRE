<?php
$bodyclass = 'page LUSTRE';
if ($is_home_page):
    $bodyclass .= ' LUSTRE-home';
endif;

echo head(array(
    'title' => metadata('LUSTRE_page', 'title'),
    'bodyclass' => $bodyclass,
    'bodyid' => metadata('LUSTRE_page', 'slug')
));
?>
<div id="primary">
    <?php if (!$is_home_page): ?>
    <p id="LUSTRE-breadcrumbs"><?php echo LUSTRE_display_breadcrumbs(); ?></p>
    <h1><?php echo metadata('LUSTRE_page', 'title'); ?></h1>
    <?php endif; ?>
    <?php
    $text = metadata('LUSTRE_page', 'text', array('no_escape' => true));
    echo $this->shortcodes($text);
    ?>
</div>

<?php echo foot(); ?>
