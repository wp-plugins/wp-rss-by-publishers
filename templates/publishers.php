<?php
$this->show_notices();
?>
<div class="wrap">
    <h2>Publishers<a href="<?php echo admin_url( 'admin.php?page=wsysadmin_publishers&action=add' ); ?>" class="add-new-h2">Add New</a>
    </h2>
    <?php $publisher_table->prepare_items(); ?>
    <?php $publisher_table->display_search(); ?>
    <form method="post" action="<?php echo admin_url('admin.php?page=wsysadmin_publishers') ?>">
        <?php $publisher_table->display(); ?>
    </form>
</div>
