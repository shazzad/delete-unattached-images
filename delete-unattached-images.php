<?php
/*
 * Plugin Name: Delete Unattached Images
 * Plugin URI: https://w4dev.com
 * Description: This plugin helps you to bulk delete unattached images from media library.
 * Version: 1.0.0
 * Author: Shazzad Hossain Khan
 * Author URI: https://shazzad.me
 */


/* Define current file as plugin file */
if (  ! defined(  'WPDUI_PLUGIN_FILE'  )  ) {
	define(  'WPDUI_PLUGIN_FILE', __FILE__  );
}


final class Delete_Unattached_Images {

	/**
	 * Plugin name
	 *
	 * @var string
	 */
	public $name = 'Delete Unattached Images';

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Class static instance\
	 *
	 * @var Delete_Unattached_Images
	 */
	protected static $instance = null;

	// static instance
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->define_constants();
		$this->init_hooks();

		do_action( 'wpdui/loaded' );
	}

	private function define_constants() {
		define( 'WPDUI_VERSION', $this->version );
		define( 'WPDUI_BASENAME', plugin_basename( WPDUI_PLUGIN_FILE ) );
	}


	/**
	 * Default hooks to run on plugin initialization.
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 60 );
		add_filter( 'plugin_action_links_' . WPDUI_BASENAME	, array( $this, 'plugin_action_links' ) );
	}


	/**
	 * Add plugin page link on plugins page table.
	 *
	 * @param  array $links Array of links.
	 *
	 * @return array        Array of merged links.
	 */
	public function plugin_action_links( $links ) {
		$links['settings'] = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'upload.php?page=wpdui' ),
			__( 'Unattached Images', 'delete-unattached-images' )
		);
		return $links;
	}


	/**
	 * Register admin menu.
	 */
	public function admin_menu() {
		// Access capability.
		$access_cap = 'manage_options';

		// Register menu.
		$admin_page = add_submenu_page(
			'upload.php',
			sprintf( '%s', __( 'Unattached Images', 'delete-unattached-images' ) ),
			__( 'Unattached Images', 'delete-unattached-images' ),
			'manage_options',
			'wpdui',
			array( $this, 'render_page' )
		 );
	}


	/**
	 * Render admin menu page.
	 */
	public function render_page() {
		$requested_action = isset( $_REQUEST['action'] ) ? trim( $_REQUEST['action'] ) : '';
		$per_page = 1000;
		$paged = isset( $_REQUEST['paged'] ) ? $_REQUEST['paged'] : 1;

        ?>

		<div class="wrap">
			<h1><?php _e( 'Unattached Images', 'delete-unattached-images' ); ?></h1>

			<?php
			if ( 'wpdui-check' == $requested_action ) {
				$unattached_images = $this->get_unattached_image( $per_page, $paged );

				if ( empty( $unattached_images ) ) {
					?>
					<div class="updated"><p>
						<?php _e( 'No unattached images found.', 'delete-unattached-images' ); ?>
					</p></div>
					<?php
				} else {
					?>
					<div class="updated">
						<p>
						<?php
						printf(
							__( 'Showing %d images by searching withing %d. There could be more.', 'delete-unattached-images' ),
							count( $unattached_images ),
							$per_page
						);
						?>
						</p>
					</div>
					<p>
						<a class="button button-primary" href="<?php echo add_query_arg( 'action', 'wpdui-delete' ); ?>">
							<?php _e( 'Delete Unattached Images', 'delete-unattached-images' ); ?>
						</a>
						<?php
						printf(
							__( ' ** maximum %d images will be deleted in each click..', 'delete-unattached-images' ),
							$per_page
						);
						?>
					</p>
					<br /><?php
					$this->display_unattached_table( $unattached_images );
				}

			} elseif ( 'wpdui-delete' == $requested_action ) {
				$unattached_images = $this->get_unattached_image( $per_page, $paged );
				if ( empty( $unattached_images ) ) {
					?>
					<div class="updated"><p>
						<?php _e( 'No unattached images found for deletion.', 'delete-unattached-images' );?>
					</p></div>
					<?php
				} else {
					?>
					<div class="updated"><p>
						<?php _e( 'Following images deleted.', 'delete-unattached-images' );?>
					</p></div>
					<ol>
						<?php
						foreach ( $unattached_images as $unattached_image ) {
							$deleted = wp_delete_post( $unattached_image['id'], true );
							if ( false !== $deleted ) {
								printf(
									'<li>%s - ( %d )</li>',
									$unattached_image['file'],
									$unattached_image['id']
								 );
							}
						}
						?>
					</ol>
					<?php
				}
			} else {
				?>
				<p>
					<a class="button" href="<?php echo add_query_arg( 'action', 'wpdui-check' ); ?>">
						<?php _e( 'Check Unattached Images', 'delete-unattached-images' ); ?>
					</a>
				</p>
				<p>
					<a class="button button-primary" href="<?php echo add_query_arg( 'action', 'wpdui-delete' ); ?>">
						<?php _e( 'Delete Unattached Images', 'delete-unattached-images' ); ?>
					</a>
					<?php
					printf(
						__( ' ** maximum %d images will be deleted in each click..', 'delete-unattached-images' ),
						$per_page
					);
					?>
				</p>
				<?php
			}
			?>
        </div>
		<?php
	}

	/**
	 * Display images table.
	 *
	 * @param  array  $unattached_images Images.
	 */
	public function display_unattached_table( $unattached_images = array() ) {
		?>
		<table class="widefat">
			<thead>
				<th><?php _e( 'Id' ); ?></th>
				<th><?php _e( 'Title' ); ?></th>
				<th><?php _e( 'File' ); ?></th>
				<th><?php _e( 'View' ); ?></th>
			</thead>
			<?php foreach ( $unattached_images as $unattached_image ) : ?>
				<tr>
					<td><?php echo $unattached_image['id']; ?></td>
					<td><?php echo $unattached_image['title']; ?></td>
					<td><?php echo $unattached_image['file']; ?></td>
					<td>
						<a target="_blank" href="<?php echo $unattached_image['url']; ?>">
							<?php _e( 'View' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}

	/**
	 * Get unattached media images.
	 *
	 * @param  integer $per_page Per page.
	 * @param  integer $paged    Current page.
	 *
	 * @return array             Array of images.
	 */
	public function get_unattached_image( $per_page = 50, $paged = 1 ) {
		$posts = get_posts( [
			'post_type'      	=> 'attachment',
		    'post_mime_type' 	=> 'image',
		    'post_status'    	=> 'inherit',
		    'posts_per_page' 	=> $per_page,
			'paged'				=> $paged,
			'post_parent'		=> '0'
		] );

		$images = [];
		foreach ( $posts as $post ) {
			$images[] = array(
				'id' 	=> $post->ID,
				'title' => $post->post_title,
				'file'	=> get_post_meta( $post->ID, '_wp_attached_file', true ),
				'url'	=> wp_get_attachment_url( $post->ID )
			);
		}

		return $this->exclude_featured_image( $images );
	}

	/**
	 * Exclude images set as post featured image.
	 *
	 * @param  array  $images Images array.
	 *
	 * @return array          Refined images array.
	 */
	public function exclude_featured_image( $images = [] ) {
		if ( empty( $images ) ) {
			return $images;
		}

		$post_types = get_post_types( ['public' => true], 'names' );
		unset( $post_types['attachment'] );
		$post_types['product_variation'] = 'product_variation';


		foreach ( $images as $k => $image ) {
			$posts = get_posts( [
				'post_type'   => $post_types,
				'post_status' => array(
					'publish',
					'pending',
					'draft',
					'future',
					'private'
				),
				'meta_key' 	  => '_thumbnail_id',
				'meta_value'  => $image['id']
			] );

			if ( ! empty( $posts ) ) {
				unset( $images[ $k ] );
			}
		}

		return $images;
	}


	/**
	 * Prettyprint array/object
	 *
	 * @param  mixed $data Data to print.
	 */
	public function p( $data ) {
		echo '<pre>';
		print_r( $data );
		echo '</pre>';
	}
}


/**
 * Plugins instance caller
 *
 * @return Delete_Unattached_Images Main plugin instance.
 */
function delete_unattached_images() {
	return Delete_Unattached_Images::instance();
}
add_action( 'init', 'delete_unattached_images' );
