<?php
/*
 * Plugin Name: Delete Unattached Images
 * Plugin URI: https://w4dev.com/contact
 * Description: This plugin helps you to bulk delete unattached images from media library.
 * Version: 1.0
 * Author: Shazzad Hossain Khan
 * Author URI: https://shazzad.me
*/


/* Define current file as plugin file */
if (! defined('WPDUI_PLUGIN_FILE')) {
	define('WPDUI_PLUGIN_FILE', __FILE__);
}


final class Delete_Unattached_Images
{
	// plugin name
	public $name = 'Delete Unattached Images';

	// plugin version
	public $version = '1.0';

	// class instance
	protected static $_instance = null;

	// static instance
	public static function instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct()
	{
		$this->define_constants();
		$this->init_hooks();

		do_action('wpdui/loaded');
	}

	private function define_constants()
	{
		define('WPDUI_NAME'				, $this->name);
		define('WPDUI_VERSION'			, $this->version);
		define('WPDUI_DIR'				, plugin_dir_path(WPDUI_PLUGIN_FILE));
		define('WPDUI_URL'				, plugin_dir_url(WPDUI_PLUGIN_FILE));
		define('WPDUI_BASENAME'			, plugin_basename(WPDUI_PLUGIN_FILE));
		define('WPDUI_SLUG'				, 'wpdui');
	}

	private function init_hooks()
	{
		add_action('admin_menu'	, [$this, 'admin_menu']	, 60);
		add_filter('plugin_action_links_' . WPDUI_BASENAME	, __CLASS__ .'::plugin_action_links');
	}

	public static function plugin_action_links($links)
	{
		$links['unattached-images'] = '<a href="'. admin_url('upload.php?page=wpdui') .'">' . __('Unattached Images', 'prpg'). '</a>';
		return $links;
	}

	public function admin_menu()
	{
		// access capability
		$access_cap = 'manage_options';

		// register menu
		$admin_page = add_submenu_page(
			'upload.php',
			sprintf('%s', __('Unattached Images', 'prpg')),
			__('Unattached Images', 'prpg'),
			'manage_options',
			'wpdui',
			[$this, 'render_page']
		);

		add_action("load-{$admin_page}"					, [$this, 'handle_actions']);
	}

	public function handle_actions()
	{
	}

	public function render_page()
	{
		$requestedAction = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';
		$per_page = 1000;
		$paged = isset($_REQUEST['paged']) ? $_REQUEST['paged'] : 1;
        ?><div class="wrap">
			<h1><?php _e('Unattached Images'); ?></h1>
			<?php
			if ('wpdui-check' == $requestedAction) {
				$unattached_images = $this->get_unattached_image($per_page, $paged);

				if (empty($unattached_images)) {
					?><div class="updated"><p>No unattached images found.</p></div><?php
				} else {
					?><div class="updated"><p>Showing <?php echo count($unattached_images); ?> images by searching withing <?php echo $per_page; ?>. There could be more.</p></div>
					<p><a class="button button-primary" href="<?php echo add_query_arg('action', 'wpdui-delete'); ?>">Delete Unattached Images</a> ** maximum <?php echo $per_page; ?> images will be deleted in each click..</p>
					<br /><?php
					$this->display_unattached_table($unattached_images);
				}
			} else if ('wpdui-delete' == $requestedAction) {
				$unattached_images = $this->get_unattached_image($per_page, $paged);
				if (empty($unattached_images)) {
					?><div class="updated"><p>No unattached images found for deletion.</p></div><?php
				} else {
					?><div class="updated"><p>Following images deleted</p></div>
					<ol><?php
					foreach ($unattached_images as $unattached_image) {
						$deleted = wp_delete_post($unattached_image['id'], true);
						if (false !== $deleted) {
							printf(
								'<li>%s - (%d)</li>',
								$unattached_image['file'],
								$unattached_image['id']
							);
						}
					}
					?></ol><?php
				}
			} else {
				?>
				<p>
					<a class="button" href="<?php echo add_query_arg('action', 'wpdui-check'); ?>">Check Unattached Images</a>
				</p>
				<p>
					<a class="button button-primary" href="<?php echo add_query_arg('action', 'wpdui-delete'); ?>">Delete Unattached Images</a> ** maximum <?php echo $per_page; ?> images will be deleted in each click..
				</p>
				<?php
			}
			?>
        </div>
		<?php
	}

	public function display_unattached_table($unattached_images = [])
	{
		?><table class="widefat">
			<thead>
				<th>Id</th>
				<th>Title</th>
				<th>File</th>
				<th>View</th>
			</thead><?php
			foreach ($unattached_images as $unattached_image) :
			?><tr>
				<td><?php echo $unattached_image['id']; ?></td>
				<td><?php echo $unattached_image['title']; ?></td>
				<td><?php echo $unattached_image['file']; ?></td>
				<td><a target="_blank" href="<?php echo $unattached_image['url']; ?>">View</a></td>
			</tr><?php
			endforeach;
		?></table><?php
	}

	public function get_unattached_image($per_page = 50, $paged = 1)
	{
		$posts = get_posts([
			'post_type'      	=> 'attachment',
		    'post_mime_type' 	=> 'image',
		    'post_status'    	=> 'inherit',
		    'posts_per_page' 	=> $per_page,
			'paged'				=> $paged,
			'post_parent'		=> '0'
		]);
		$images = [];
		foreach ($posts as $post) {
			$images[] = [
				'id' 	=> $post->ID,
				'title' => $post->post_title,
				'file'	=> get_post_meta($post->ID, '_wp_attached_file', true),
				'url'	=> wp_get_attachment_url($post->ID)
			];
		}

		$images = $this->exclude_featured_image($images);

		return $images;

		#$this->p($images);
	}

	public function exclude_featured_image($images = [])
	{
		if (empty($images)) {
			return $images;
		}

		$post_types = get_post_types(['public' => true], 'names');
		unset($post_types['attachment']);
		$post_types['product_variation'] = 'product_variation';


		foreach ($images as $k => $image) {
			$posts = get_posts([
				'post_type' 	=>$post_types,
				'post_status' 	=> ['publish', 'pending', 'draft', 'future', 'private'],
				'meta_key' 		=> '_thumbnail_id',
				'meta_value' 	=> $image['id']
			]);

			if (! empty($posts)) {
				unset($images[$k]);
			}
		}

		return $images;
	}

	public function p($data)
	{
		echo '<pre>';
		print_r($data);
		echo '</pre>';
	}
}

/* Initialize */
add_action('plugins_loaded', function(){
	Delete_Unattached_Images::instance();
});
