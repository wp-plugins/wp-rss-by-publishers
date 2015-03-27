<?php
$this->show_notices();
?>
    <div class="wrap">
        <h2><?php if(isset($item) && isset($item['id'])) echo 'Edit'; else echo 'Add'; ?> feed for: <?php echo $publisher['name'] ?></h2>
        <div id="feed-finder">
        <?php if (count($feeds) > 0): ?>
            <h3>Feeds Found on URL: <em><?php echo $url ?></em></h3>
            <?php if (count($feeds) > 1) :
                $option_template = 'Option %d: ';
                $form_class = ' class="multi"';
            ?>
            <p><strong>This web page provides at least <?php print count($feeds); ?> different feeds.</strong> These feeds may provide the same information
                in different formats, or may track different items. (You can check the Feed Information and the
                Sample Item for each feed to get an idea of what the feed provides.) Please select the feed that you'd like to subscribe to.</p>
            <?php else :
                $option_template = '';
                $form_class = '';
            endif;

            foreach ($feeds as $key => $f):
                require_once(ABSPATH . WPINC . '/class-feed.php');
                require_once(__DIR__ . '/../classes/feedwordpress/syndicatedpost.class.php');

                $pie = new SimplePie();

                $pie->set_sanitize_class( 'WP_SimplePie_Sanitize_KSES' );
                // We must manually overwrite $feed->sanitize because SimplePie's
                // constructor sets it before we have a chance to set the sanitization class
                $pie->sanitize = new WP_SimplePie_Sanitize_KSES();

                $pie->set_cache_class( 'WP_Feed_Cache' );
                $pie->set_file_class( 'WP_SimplePie_File' );

                $pie->set_feed_url( $f );
                $pie->set_timeout( WSYS_Feed::DEFAULT_TIMEOUT );
                $pie->enable_cache(false);
                $pie->init();
                $pie->handle_content_type();

                if ($pie->error()) {
                    $pie = new WP_Error( 'simplepie-error', $pie->error() );
                }


//                $pie = FeedWordPress::fetch($f, array("cache" => false));
                $rss = (is_wp_error($pie) ? $pie : new MagpieFromSimplePie($pie));

                if ($rss and !is_wp_error($rss)):
                    $feed_link = (isset($rss->channel['link'])?$rss->channel['link']:'');
                    $feed_title = (isset($rss->channel['title'])?$rss->channel['title']:$feed_link);
                    $feed_type = ($rss->feed_type ? $rss->feed_type : 'Unknown');
                    $feed_version_template = '%.1f';
                    $feed_version = $rss->feed_version;
                else :
                    // Give us some sucky defaults
                    $feed_title = feedwordpress_display_url($url);
                    $feed_link = $url;
                    $feed_type = 'Unknown';
                    $feed_version_template = '';
                    $feed_version = '';
                endif;
                ?>
            <form<?php print $form_class; ?> action="" method="post">
                <div class="inside">
                <?php
                $classes = array('feed-found'); $currentFeed = '';
                if (in_array($f,$subscribed_feeds)) :
                    $classes[] = 'current';
                    $currentFeed = ' (currently subscribed)';
                endif;
                if ($key%2) :
                    $classes[] = 'alt';
                endif;
                ?>
                    <fieldset class="<?php print implode(" ", $classes); ?>">
                        <legend><?php printf($option_template, ($key+1)); print $feed_type." "; printf($feed_version_template, $feed_version); ?> feed<?php print $currentFeed; ?></legend>
                        <input type="hidden" name="name" value="<?php echo esc_html($feed_title); ?>" />
                        <input type="hidden" name="url" value="<?php echo esc_html($f); ?>" />
                        <input type="hidden" name="status" value="<?php echo WSYS_Feed::STATUS_ACTIVE ?>" />
                        <div>
                            <div class="feed-sample">
                <?php
                $link = NULL;
                $post = NULL;
                if (!is_wp_error($rss) and count($rss->items) > 0):
                    // Prepare to display Sample Item
                    $link = new MagpieMockLink(array('simplepie' => $pie, 'magpie' => $rss), $f);
                    $post = new SyndicatedPost(array('simplepie' => $rss->originals[0], 'magpie' => $rss->items[0]), $link);
                    ?>
                                <h3>Sample Item</h3>
                                <ul>
                                    <li><strong>Title:</strong> <a href="<?php echo $post->post['meta']['syndication_permalink']; ?>"><?php echo $post->post['post_title']; ?></a></li>
                                    <li><strong>Date:</strong> <?php print date('d-M-y g:i:s a', $post->published()); ?></li>
                                </ul>
                                <div class="entry">
                                    <?php print $post->post['post_content']; ?>
                                </div>
                    <?php
                    do_action('feedwordpress_feed_finder_sample_item', $f, $post, $link);
                else:
                    if (is_wp_error($rss)) : ?>
                                <div class="feed-problem">
                                    <h3>Problem:</h3>
                                    <p>FeedWordPress encountered the following error when trying to retrieve this feed:</p>
                                    <p style="margin: 1.0em 3.0em"><code><?php echo $rss->get_error_message() ?></code></p>
                                    <p>If you think this is a temporary problem, you can still force FeedWordPress to add the subscription. FeedWordPress will not be able to find any syndicated posts until this problem is resolved.</p>
                                </div>
                    <?php endif; ?>
                                <h3>No Items</h3>
                                <p>FeedWordPress found no posts on this feed.</p>
                <?php endif; ?>
                            </div>
                            <div>
                                <h3>Feed Information</h3>
                                <ul>
                                    <li><strong>Homepage:</strong> <a href="<?php echo $feed_link; ?>"><?php echo is_null($feed_title)?'<em>Unknown</em>':$feed_title; ?></a></li>
                                    <li><strong>Feed URL:</strong> <a title="<?php echo esc_html($f); ?>" href="<?php echo esc_html($f); ?>"><?php echo esc_html($f); ?></a> (<a title="Check feed &lt;<?php echo esc_html($f); ?>&gt; for validity" href="http://feedvalidator.org/check.cgi?url=<?php echo urlencode($f); ?>">validate</a>)</li>
                                    <li><strong>Encoding:</strong> <?php echo isset($rss->encoding)?esc_html($rss->encoding):"<em>Unknown</em>"; ?></li>
                                    <li><strong>Description:</strong> <?php echo isset($rss->channel['description'])?esc_html($rss->channel['description']):"<em>Unknown</em>"; ?></li>
                                </ul>
                                <?php //do_action('feedwordpress_feedfinder_form', $f, $post, $link, $this->for_feed_settings()); ?>
                                <?php if(!in_array($f,$subscribed_feeds)) : ?>
                                <div class="submit">
                                    <input type="submit" class="button-primary" name="Use" value="&laquo; Add this feed" />
                                </div>
                                <?php endif ?>
                            </div>
                        </div>
                    </fieldset>
                </div> <!-- class="inside" -->
            </form>
                <?php
                unset($link);
                unset($post);
            endforeach;
        else:
            $url = esc_html($url);
            ?>
            <h3>Searched for feeds at <em><?php echo $url ?></em></h3>
            <p><strong><?php echo __('Error') ?>:</strong> <?php echo __("FeedWordPress couldn't find any feeds at").' <code><a href="'.htmlspecialchars($url).'">'.htmlspecialchars($url).'</a></code>. '.__('Try another URL') ?>.</p>
        <?php endif; ?>
            <form action="<?php echo admin_url('admin.php') ?>">
                <input type="hidden" name="page" value="wsysadmin_feeds" />
                <input type="hidden" name="action" value="add" />
                <input type="hidden" name="publisher_id" value="<?php echo $publisher_id ?>" />
<!--                <input type="hidden" name="noheader" value="true" />-->
                <div class="inside">
                    <fieldset class="alt" style="margin: 1.0em 3.0em; font-size: smaller;">
                        <legend>Alternative feeds</legend>
                        <h3>Use a different feed</h3>
                        <div>
                            <label>Address:
                                <input type="text" name="url" id="use-another-feed" placeholder="URL" value="<?php echo (isset($_GET['url']) && $_GET['url']!='URL') ? $_GET['url'] : '' ?>" size="64" style="max-width: 80%" />
                            </label>
                            <input type="submit" class="button-primary" value="Check &raquo;" />
                        </div>
                        <p>This can be the address of a feed, or of a website. We will try to automatically detect any feeds associated with a website.</p>
                    </fieldset>
                </div>
            </form>
        </div>
        <a class="button" href="<?php echo admin_url( WP_WsysAdmin::build_url('admin.php','wsysadmin_publishers','edit',array('id'=>$publisher_id))) ?>">Return to publisher page</a>
    </div>
<?php
