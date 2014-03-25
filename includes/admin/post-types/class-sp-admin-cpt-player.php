<?php
/**
 * Admin functions for the players post type
 *
 * @author 		ThemeBoy
 * @category 	Admin
 * @package 	SportsPress/Admin/Post Types
 * @version     0.7
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'SP_Admin_CPT' ) )
	include( 'class-sp-admin-cpt.php' );

if ( ! class_exists( 'SP_Admin_CPT_Player' ) ) :

/**
 * SP_Admin_CPT_Player Class
 */
class SP_Admin_CPT_Player extends SP_Admin_CPT {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'sp_player';

		// Post title fields
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );

		// Admin Columns
		add_filter( 'manage_edit-sp_player_columns', array( $this, 'edit_columns' ) );
		add_action( 'manage_sp_player_posts_custom_column', array( $this, 'custom_columns' ), 2, 2 );
		add_filter( 'manage_edit-sp_player_sortable_columns', array( $this, 'custom_columns_sort' ) );

		// Filtering
		add_action( 'restrict_manage_posts', array( $this, 'filters' ) );
		add_filter( 'parse_query', array( $this, 'filters_query' ) );
		
		// Call SP_Admin_CPT constructor
		parent::__construct();
	}

	/**
	 * Change title boxes in admin.
	 * @param  string $text
	 * @param  object $post
	 * @return string
	 */
	public function enter_title_here( $text, $post ) {
		if ( $post->post_type == 'sp_player' )
			return __( 'Name', 'sportspress' );

		return $text;
	}

	/**
	 * Change the columns shown in admin.
	 */
	public function edit_columns( $existing_columns ) {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'sp_number' => '<span class="dashicons sp-icon-tshirt tips" title="' . __( 'Number', 'sportspress' ) . '"></span>',
			'title' => __( 'Name', 'sportspress' ),
			'sp_position' => __( 'Positions', 'sportspress' ),
			'sp_team' => __( 'Teams', 'sportspress' ),
			'sp_league' => __( 'Leagues', 'sportspress' ),
			'sp_season' => __( 'Seasons', 'sportspress' ),
			'sp_views' => __( 'Views', 'sportspress' ),
		);
		return $columns;
	}

	/**
	 * Define our custom columns shown in admin.
	 * @param  string $column
	 */
	public function custom_columns( $column, $post_id ) {
		switch ( $column ):
			case 'sp_position':
				echo get_the_terms( $post_id, 'sp_position' ) ? the_terms( $post_id, 'sp_position' ) : '&mdash;';
			break;
			case 'sp_team':
				$teams = (array)get_post_meta( $post_id, 'sp_team', false );
				$teams = array_filter( $teams );
				if ( empty( $teams ) ):
					echo '&mdash;';
				else:
					$results = get_post_meta( $post_id, 'sp_results', true );
					global $sportspress_options;
					$main_result = sportspress_array_value( $sportspress_options, 'main_result', null );
					foreach( $teams as $team_id ):
						if ( ! $team_id ) continue;
						$team = get_post( $team_id );

						if ( $team ):
							$team_results = sportspress_array_value( $results, $team_id, null );

							if ( $main_result ):
								$team_result = sportspress_array_value( $team_results, $main_result, null );
							else:
								if ( is_array( $team_results ) ):
									end( $team_results );
									$team_result = prev( $team_results );
								else:
									$team_result = null;
								endif;
							endif;

							if ( $team_result != null ):
								unset( $team_results['outcome'] );
								$team_results = implode( ' | ', $team_results );
								echo '<a class="result tips" title="' . $team_results . '" href="' . get_edit_post_link( $post_id ) . '">' . $team_result . '</a> ';
							endif;

							echo $team->post_title;

							echo '<br>';
						endif;
					endforeach;
				endif;
			break;
			case 'sp_league':
				echo get_the_terms ( $post_id, 'sp_league' ) ? the_terms( $post_id, 'sp_league' ) : '&mdash;';
			break;
			case 'sp_season':
				echo get_the_terms ( $post_id, 'sp_season' ) ? the_terms( $post_id, 'sp_season' ) : '&mdash;';
			break;
			case 'sp_venue':
				echo get_the_terms ( $post_id, 'sp_venue' ) ? the_terms( $post_id, 'sp_venue' ) : '&mdash;';
			break;
			case 'sp_views':
	        	echo sportspress_get_post_views( $post_id );
			break;
		endswitch;
	}

	/**
	 * Make columns sortable
	 *
	 * https://gist.github.com/906872
	 *
	 * @access public
	 * @param mixed $columns
	 * @return array
	 */
	public function custom_columns_sort( $columns ) {
		$custom = array(
			'sp_views'		=> 'sp_views',
		);
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Show a category filter box
	 */
	public function filters() {
		global $typenow, $wp_query;

	    if ( $typenow != 'sp_player' )
	    	return;

		sportspress_highlight_admin_menu();

		$selected = isset( $_REQUEST['team'] ) ? $_REQUEST['team'] : null;
		$args = array(
			'post_type' => 'sp_team',
			'name' => 'team',
			'show_option_none' => __( 'Show all teams', 'sportspress' ),
			'selected' => $selected,
			'values' => 'ID',
		);
		wp_dropdown_pages( $args );

		$selected = isset( $_REQUEST['sp_league'] ) ? $_REQUEST['sp_league'] : null;
		$args = array(
			'show_option_all' =>  __( 'Show all leagues', 'sportspress' ),
			'taxonomy' => 'sp_league',
			'name' => 'sp_league',
			'selected' => $selected
		);
		sportspress_dropdown_taxonomies( $args );

		$selected = isset( $_REQUEST['sp_season'] ) ? $_REQUEST['sp_season'] : null;
		$args = array(
			'show_option_all' =>  __( 'Show all seasons', 'sportspress' ),
			'taxonomy' => 'sp_season',
			'name' => 'sp_season',
			'selected' => $selected
		);
		sportspress_dropdown_taxonomies( $args );
	}

	/**
	 * Filter in admin based on options
	 *
	 * @param mixed $query
	 */
	public function filters_query( $query ) {
		global $typenow, $wp_query;

	    if ( $typenow == 'sp_player' ) {

	    	if ( isset( $_GET['team'] ) ) {
		    	$query->query_vars['meta_value'] 	= $_GET['team'];
		        $query->query_vars['meta_key'] 		= 'sp_team';
		    }
		}
	}
}

endif;

return new SP_Admin_CPT_Player();
