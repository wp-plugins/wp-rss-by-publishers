<div class="wrap">
    <h2>Fetching posts from <?php echo $item->data['name'] ?></h2>
    <p>Duration: <strong><?php echo $results['duration'].'</strong> '.($results['duration']==1 ? 'second' : 'seconds'); ?></p>
    <p>Posts found in feed: <strong><?php echo $results['total']; ?></strong></p>
    <p>Posts inserted: <strong><?php echo $results['inserted']; ?></strong></p>
    <p>Posts updated: <strong><?php echo $results['updated']; ?></strong></p>
    <p>Posts left unchanged: <strong><?php echo $results['no_change']; ?></strong></p>
    <p>Posts with errors: <strong><?php echo $results['errored']; ?></strong></p>
    <p>Posts without images: <strong><?php echo $results['without_image']; ?></strong></p>
    <a class="button" href="<?php echo admin_url( WP_WsysAdmin::build_url('admin.php','wsysadmin_publishers','edit',array('id'=>$item->data['publisher_id']))) ?>">Return to publisher page</a>
    <?php if($results['messages']): ?>
    <fieldset>
        <legend>Log</legend>
        <?php echo nl2br($results['messages']) ?>
    </fieldset>
    <?php endif ?>
</div>