<?php
$head = array('bodyclass' => 'LUSTRE primary',
              'title' => html_escape(__('LUSTRE | Browse')),
              'content_class' => 'horizontal-nav');
echo head($head);
?>
<ul id="section-nav" class="navigation">
    <li class="<?php if (isset($_GET['view']) &&  $_GET['view'] != 'hierarchy') {echo 'current';} ?>">
        <a href="<?php echo html_escape(url('LUSTRE/index/browse?view=list')); ?>"><?php echo __('List View'); ?></a>
    </li>
    <li class="<?php if (isset($_GET['view']) && $_GET['view'] == 'hierarchy') {echo 'current';} ?>">
        <a href="<?php echo html_escape(url('LUSTRE/index/browse?view=hierarchy')); ?>"><?php echo __('Hierarchy View'); ?></a>
    </li>
</ul>
<?php echo flash(); ?>

<a class="add-page button small green" href="<?php echo html_escape(url('LUSTRE/index/add')); ?>"><?php echo __('Add a Page'); ?></a>
<?php if (!has_loop_records('LUSTRE_page')): ?>
    <p><?php echo __('There are no pages.'); ?> <a href="<?php echo html_escape(url('LUSTRE/index/add')); ?>"><?php echo __('Add a page.'); ?></a></p>
<?php else: ?>
    <?php if (isset($_GET['view']) && $_GET['view'] == 'hierarchy'): ?>
        <?php echo $this->partial('index/browse-hierarchy.php', array('LUSTRE' => $LUSTRE_pages)); ?>
    <?php else: ?>
        <?php echo $this->partial('index/browse-list.php', array('LUSTRE' => $LUSTRE_pages)); ?>
    <?php endif; ?>    
<?php endif; ?>
<?php echo foot(); ?>
