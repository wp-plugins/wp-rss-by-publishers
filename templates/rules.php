<div class="wrap" style="margin-left:20px">
    <h3>Rules<a href="<?php echo admin_url( WP_WsysAdmin::build_url('admin.php','wsysadmin_rules','add',array('publisher_id'=>$item['id']))); ?>" class="add-new-h2">Add New</a>
    </h3>
    <?php if(count($rules)>0): ?>
    <form method="post" action="<?php echo admin_url( WP_WsysAdmin::build_url('admin.php','wsysadmin_rules','add',array('id'=>$item['id']))); ?>">
        <table class="wp-list-table widefat fixed rules">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Feed</th>
                    <th>Tags</th>
                    <th>Categories</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <?php foreach($rules as $rule): ?>
                <?php $categories = explode(',',$rule['categories']) ?>
                <?php $category_names = array() ?>
                <?php foreach($categories as $category) { $category_names[] = get_cat_name($category); } ?>
                <?php if($rule['feed_id']) $rule_feed = WSYS_Feed::get($rule['feed_id']) ?>
            <tbody>
            <tr>
                <td><?php echo $rule['id'] ?></td>
                <td><?php echo ($rule['feed_id'] ? $rule_feed['name'] : 'All') ?></td>
                <td><?php echo $rule['tags'] ?></td>
                <td><?php echo implode(',',$category_names) ?></td>
                <td>
                    <a class="button" href="<?php echo admin_url( WP_WsysAdmin::build_url('admin.php','wsysadmin_rules','edit',array('id'=>$rule['id'], 'publisher_id'=>$item['id']))); ?>">Edit</a>
                    <a class="button confirm" href="<?php echo admin_url( WP_WsysAdmin::build_url('admin.php','wsysadmin_rules','delete',array('id'=>$rule['id'], 'publisher_id'=>$item['id']))); ?>" data-confirmation="Are you sure you want to delete this rule?">Delete</a>
                </td>
            </tr>
            </tbody>
            <?php endforeach ?>
        </table>
    </form>
    <?php else: ?>
        <p>No rules defined</p>
    <?php endif ?>
    <br/>
</div>
