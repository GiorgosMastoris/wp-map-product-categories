<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://#
 * @since             1.0.0
 * @package           Map_product_categories
 *
 * @wordpress-plugin
 * Plugin Name:       Map Product Categories 
 * Plugin URI:        https://#
 * Description:       Tranfer all products from one category to another
 * Version:           1.0.0
 * Author:            Giorgos Mastoris
 * Author URI:        https://#
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       map_product_categories
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'MAP_PRODUCT_CATEGORIES _VERSION', '1.0.0' );

add_action( 'admin_menu', 'mapping_menu_page' );

function mapping_menu_page() {
	add_menu_page( 'Map Product Categories', 'Map Product Categories', 'manage_options', 'mappcategory/mappcategory-admin-page.php', 'myplguin_admin_page', 'dashicons-tickets', 6  );
}

function myplguin_admin_page() {
	?>

		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css">

		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.min.js"></script>

		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>

		<?php		

		global $wpdb ;
		$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
		$limit = 100;
		$offset = ( $pagenum - 1 ) * $limit;
		$total = $wpdb->get_var( "SELECT COUNT(`term_id`) FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy = 'product_cat' AND count != 0" );
		$num_of_pages = ceil( $total / $limit );
		$product_categories = $wpdb->get_results( "SELECT * FROM wp_terms AS t LEFT JOIN wp_term_taxonomy AS tt ON (t.term_id = tt.term_id) WHERE tt.taxonomy = 'product_cat' AND tt.count != 0 ORDER BY `tt`.`count` DESC LIMIT $offset, $limit") ;
		$product_main_categories =  $wpdb->get_results( "SELECT * FROM wp_terms AS t LEFT JOIN wp_term_taxonomy AS tt ON (t.term_id = tt.term_id) WHERE tt.taxonomy = 'product_cat'") ; ?>

		
		<div class="container mt-5">
		<h2 class="text-center"> Transfer your product category to another</h2>
			<table class="table table-dark mt-5">
				<thead>
					<tr>
					<th scope="col"></th>
					<th scope="col">Product category name</th>
					<th scope="col">Product category to map</th>
					<th scope="col">Count</th>
					<th scope="col">Relation to product category</th>
					<th scope="col">Action</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($product_categories as $cat): ?>
					<?php 
						$cat = get_term($cat->term_id, 'product_cat');
						$relation = get_term_meta($cat->term_id, 'product_cat_relation',true); ?>
					<?php 

					?>
					<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?page=mappcategory%2Fmappcategory-admin-page.php" >
					<tr>
						<th scope="row"><input name="term_id" type="text" value="<?php echo $cat->term_id ; ?>" hidden></th>
						<td name="name"> <?php echo $cat->name ; ?></td>
						<td><select name="transfer_id" class="form-select" aria-label="Default select example">
							<option value="" selected>Select Category for map</option>
							<?php foreach($product_main_categories as $category): ?>
								<option value="<?php echo $category->term_id ; ?>"><?php echo $category->name ; ?></option>
							<?php endforeach; ?>	
							</select>
						</td>
						<td> <?php echo $cat->count ; ?></td>
						<td> <?php echo ($relation) ? $relation->name : 'Not relation' ;    ?></td>
						<td><button type="submit" class="btn btn-success">Save</button></td>
					</tr>
						
					</form>
					<?php endforeach; ?>
				</tbody>
			</table>

		</div>
		<div class="container">
			<div class="row">
				<div class="col-sm">
				<form method="post" id="map_products" action="<?php echo $_SERVER['PHP_SELF'];?>?page=mappcategory%2Fmappcategory-admin-page.php">
					
					<input type="hidden" name="map_products" value="true">
					<button type="submit" class="btn btn-primary">Map Products</button>
				</form>
				</div>
				<div class="col-sm">
				<?php 
					$page_links = paginate_links( array(
								'base' => add_query_arg( 'pagenum', '%#%' ),
								'format' => '',
								'prev_text' => __( '«', 'text-domain' ),
								'next_text' => __( '»', 'text-domain' ),
								'total' => $num_of_pages,
								'current' => $pagenum
							) );

						if ( $page_links ) {
							echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
						} ?>
				</div>
			</div>
		</div>

		<?php
			if ($_SERVER["REQUEST_METHOD"] == "POST") {

				if(isset($_POST['map_products'])){
					transferProductCategory();
					header("Refresh:0");
					return ;
				}

				$current_id = $_POST['term_id'] ;
				$transfer_id =  $_POST['transfer_id'] ;

				if(!empty($current_id) && !empty($transfer_id) ){
					$term = get_term_by ('id',$transfer_id,'product_cat');
					
					if($term){
						update_term_meta($current_id, 'product_cat_relation',$term);
						header("Refresh:0");
					}

				}
			}
		?>
	<?php
}

function transferProductCategory(){
	global $wpdb ;

	$product_cats = $wpdb->get_results( "SELECT * FROM wp_terms AS t LEFT JOIN wp_termmeta AS tm ON (t.term_id = tm.term_id) WHERE tm.meta_key = 'product_cat_relation' AND tm.meta_value != '' ") ;
	

	//transger terms to products
	foreach ($product_cats as $term){

		$relation_term = get_term_meta($term->term_id, 'product_cat_relation',true); 
		
		if(term_exists($relation_term->term_id,'product_cat')){


			$products = get_posts( array(
				'post_type' => 'product',
				'numberposts' => -1,
				'fields'        => 'ids',
				'tax_query' => array(
					array(
						'taxonomy' => 'product_cat',
						'field' => 'id',
						'terms' => $term->term_id,
						'operator' => 'IN',
						)
					),
				));
	
				if($products){
					$value = $relation_term->term_id;
					$order = 0;

					$productsInsert = $products ;
					$productsInsert = implode(', ', array_map(function ($item) use ($value,$order) {
						return '('.$item . ', '.$value.' , '.$order.')' ;
					  }, $productsInsert));

					$productsIn = implode(",",$products);

					$product_cats = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO wp_term_relationships (object_id, term_taxonomy_id, term_order) VALUES $productsInsert"));

					$unset = $wpdb->query( $wpdb->prepare( "DELETE FROM wp_term_relationships WHERE term_taxonomy_id = $term->term_id AND object_id IN ($productsIn)" )) ;
					
					wp_update_term_count($relation_term->term_id,'product_cat');

					wp_update_term_count($term->term_id,'product_cat');
				}

	
		}
	}

}