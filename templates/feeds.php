<div class="wrap" style="margin-left:20px">
    <h3>Feeds<a href="<?php echo admin_url( WP_WsysAdmin::build_url('admin.php','wsysadmin_feeds','add',array('publisher_id'=>$feed_table->publisher_id))); ?>" class="add-new-h2">Add New</a>
    </h3>
    <?php $feed_table->prepare_items(); ?>
    <form method="get" action="<?php echo admin_url(); ?>">
        <input type="hidden" name="page" value="wsysadmin_feeds" />
        <input type="hidden" name="publisher_id" value="<?php echo $feed_table->publisher_id ?>" />
<!--        <input type="hidden" name="noheader" value="true" />-->
        <?php $feed_table->display(); ?>
    </form>
</div>
