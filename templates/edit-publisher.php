<?php
$this->show_notices();
?>
<div class="wrap">
    <h2><?php if(isset($item) && isset($item['id'])) echo 'Edit'; else echo 'Add'; ?> publisher</h2>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <form method="post" action="<?php echo admin_url( 'admin.php?page=wsysadmin_publishers&action=' . $_GET['action'] . ($_GET['action']=='edit' ? '&id='.$_GET['id'] : '') ); ?>">
                    <div id="post-body-content" style="position: relative;">
                        <div id="titlediv">
                            <div id="titlewrap">
                                <label for="title">Name</label>
                                <input type="text" name="name" size="30" value="<?php if(isset($item)) echo $item['name'] ?>" id="title" spellcheck="true" autocomplete="off" />
                            </div>
                        </div>
                        <div>
                            <label for="url">URL</label>
                            <br class="clear" />
                            <input type="text" name="url" style="width:100%" value="<?php if(isset($item)) echo $item['url'] ?>" id="url" spellcheck="true" autocomplete="off" />
                        </div>
                        <div>
                            <label for="description">Description</label>
                            <br class="clear" />
                            <textarea id="description" name="description" style="width:100%" spellcheck="true" autocomplete="off" rows="8"><?php if(isset($item)) echo $item['description'] ?></textarea>
                        </div>
                    </div>

                    <div id="postbox-container-1" class="postbox-container">
                        <div id="submitdiv" class="postbox">
                            <div class="inside">
                                <div class="submitbox" id="submitpost">
                                    <div id="misc-publishing-actions">
                                        <div class="misc-pub-section misc-pub-post-status">
                                            <div>
                                                <label for="status">Status</label>
                                                <select id="status" name="status">
                                                    <?php foreach(WSYS_Publisher::getStatuses() as $status => $label) { ?>
                                                        <option value="<?php echo $status ?>" <?php if(isset($item) && $item['status']==$status) echo 'selected=""'; ?>><?php echo $label ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <?php if(isset($item) && isset($item['created_at'])) { ?>
                                        <div class="misc-pub-section misc-pub-visibility">
                                            <span id="timestamp">Created at: <b><?php echo $item['created_at'] ?></b></span>
                                        </div>
                                        <?php } ?>
                                        <?php if(isset($item) && isset($item['api_key'])) { ?>
                                        <div class="misc-pub-section misc-pub-visibility">
                                            <span>API KEY: <b><?php echo $item['api_key'] ?></b></span>
                                        </div>
                                        <?php } ?>
                                    </div>
                                    <div class="clear"></div>

                                    <div id="major-publishing-actions">
                                        <div id="delete-action">
                                            <a class="submitdelete deletion" href="<?php echo admin_url( 'admin.php?page=wsysadmin_publishers' ) ?>">Cancel</a></div>

                                        <div id="publishing-action">
                                            <input name="save" type="submit" class="button button-primary button-large" value="Save" />
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="postbox">
                            <div class="inside">
                                <div class="misc-pub-section misc-pub-visibility">
                                    <h3>Primary image</h3>
                                    <input type="hidden" id="image_1" name="image_1" value="<?php if(isset($item)) echo $item['image_1'] ?>" />
                                    <div class="publisher-image" <?php if(!isset($item) || !$item['image_1']) echo 'style="display:none"' ?>>
                                        <img src="<?php if(isset($item) && $item['image_1']) echo $item['image_1'] ?>" id="image_1_preview" />
                                    </div>
                                    <?php if(isset($item) && $item['image_1']) : ?>
                                        <button class="meta-image-button" rel="image_1">Change image</button>
                                    <?php else: ?>
                                        <p class="no-image">No image</p>
                                        <button class="meta-image-button" rel="image_1">Select image</button>
                                    <?php endif ?>
                                    <br/>
                                    <br/>
                                    <h3>Second image</h3>
                                    <input type="hidden" id="image_2" name="image_2" value="<?php if(isset($item)) echo $item['image_2'] ?>" />
                                    <div class="publisher-image" <?php if(!isset($item) || !$item['image_2']) echo 'style="display:none"' ?>>
                                        <img src="<?php if(isset($item) && $item['image_2']) echo $item['image_2'] ?>" id="image_2_preview" />
                                    </div>
                                    <?php if(isset($item) && $item['image_2']) : ?>
                                        <button class="meta-image-button" rel="image_2">Change image</button>
                                    <?php else: ?>
                                        <p class="no-image">No image</p>
                                        <button class="meta-image-button" rel="image_2">Select image</button>
                                    <?php endif ?>
                                    <br/>
                                    <br/>
                                    <h3>Third image</h3>
                                    <input type="hidden" id="image_3" name="image_3" value="<?php if(isset($item)) echo $item['image_3'] ?>" />
                                    <div class="publisher-image" <?php if(!isset($item) || !$item['image_3']) echo 'style="display:none"' ?>>
                                        <img src="<?php if(isset($item) && $item['image_3']) echo $item['image_3'] ?>" id="image_3_preview" />
                                    </div>
                                    <?php if(isset($item) && $item['image_3']) : ?>
                                        <button class="meta-image-button" rel="image_3">Change image</button>
                                    <?php else: ?>
                                        <p class="no-image">No image</p>
                                        <button class="meta-image-button" rel="image_3">Select image</button>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <?php if(isset($item) && isset($item['id'])) { ?>
                <div id="postbox-container-2" class="postbox-container">
                    <div class="postbox">
                        <?php include('feeds.php') ?>
                    </div>
                </div>
                <div id="postbox-container-3" class="postbox-container">
                    <div class="postbox">
                        <?php include('rules.php') ?>
                    </div>
                </div>
                <?php } ?>
            </div><!-- /post-body -->
            <br class="clear">
        </div>
    </form>
</div>
<?php
