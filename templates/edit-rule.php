<?php
$this->show_notices();
?>
    <div class="wrap">
        <h2><?php if(isset($item) && isset($item['id'])) echo 'Edit'; else echo 'Add'; ?> rule</h2>
        <form method="post" action="" class="rule-form">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content" style="position: relative;">
                        <h3>Assign all posts from </h3>
                        <div>
                            <select class="full-width" name="feed">
                                <option value="">All feeds</option>
                                <?php if(isset($feeds) && count($feeds)>0): ?>
                                    <?php foreach($feeds as $feed): ?>
                                        <option value="<?php echo $feed['id'] ?>" <?php if($feed['id'] == $item['feed_id']): ?>selected="selected"<?php endif; ?>><?php echo $feed['name'] ?></option>
                                    <?php endforeach ?>
                                <?php endif ?>
                            </select>
                        </div>
                        <br/>
                        <h3>Matching tags </h3>
                        <div>
                            <label for="tags">Enter tags separated by comma</label>
                            <br/>
                            <textarea name="tags" id="rule_tags" class="full-width" <?php if(isset($item['tags']) && !$item['tags']) echo 'disabled=""' ?>><?php if(isset($item['tags']) && $item['tags']) echo $item['tags'] ?></textarea>
                            <label for="rule_tags_any"><input type="checkbox" id="rule_tags_any" <?php if(isset($item['tags']) && !$item['tags']) echo 'checked=""' ?>/>
                                Any (tags will not be taken into consideration, all posts being directed to the chosen categories)
                            </label>
                        </div>
                        <br/>
                        <h3>To categories </h3>
                        <div>
                            <?php /*$categories = get_categories(array('hide_empty'=>0)); ?>
                            <?php foreach($categories as $category): ?>
                                <div class="category-input">
                                    <label for="category_<?php echo $category->cat_ID ?>"><input type="checkbox" id="category_<?php echo $category->cat_ID ?>" name="categories[]" value="<?php echo $category->cat_ID ?>" <?php if(isset($item) && in_array($category->cat_ID, $item['categories'])) echo 'checked=""' ?>/><?php echo $category->name ?></label>
                                </div>
                            <?php endforeach */ ?>
                            <ul class="category-list">
                            <?php wp_category_checklist(0,0,(isset($item['categories']) ? $item['categories'] : false),false,null,false); ?>
                            </ul>
                        </div>
                    </div>
                    <div id="postbox-container-1" class="postbox-container">
                        <div id="submitdiv" class="postbox">
                            <div class="inside">
                                <div class="submitbox" id="submitpost">
                                    <div id="major-publishing-actions">
                                        <div id="delete-action">
                                            <a class="submitdelete deletion" href="<?php echo admin_url( WP_WsysAdmin::build_url('admin.php','wsysadmin_publishers','edit',array('id'=>$item['publisher_id']))); ?>">Cancel</a></div>

                                        <div id="publishing-action">
                                            <input name="save" type="submit" class="button button-primary button-large" value="Save" />
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /post-body -->
                <br class="clear">
            </div>
        </form>
    </div>
<?php
