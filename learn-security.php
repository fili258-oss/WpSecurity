<?php
/**
 * Plugin Name: WP Learn Security
 * Description: learn security best practices for WordPress development
 * Author: Marino Botina
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) )
{
    exit; // Exit if accessed directly
}

define('WPLEARN_SUCCESS_PAGE_SLUG', home_url('/success'));
define('WPLEARN_ERROR_PAGE_SLUG', home_url('/error'));

/**
 * Setting up some URL constants
 */
define( 'WPLEARN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPLEARN_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Enqueue Admin assets
 */
add_action( 'admin_enqueue_scripts', 'wp_learn_enqueue_script' );
function wp_learn_enqueue_script() {
	wp_register_script(
		'wp-learn-admin',
		WPLEARN_PLUGIN_URL . 'assets/js/admin.js',
		array( 'jquery' ),
		'1.0.0',
		true
	);
	wp_enqueue_script( 'wp-learn-admin' );
	wp_localize_script(
		'wp-learn-admin',
		'wp_learn_ajax',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		)
	);
}

/**
 * Enqueue Frontend assets
 */
add_action( 'wp_enqueue_scripts', 'wp_learn_enqueue_script_frontend' );
function wp_learn_enqueue_script_frontend() {
	wp_register_style(
		'wp-learn-style',
		WPLEARN_PLUGIN_URL . 'assets/css/style.css',
		array(),
		'1.0.0'
	);
	wp_enqueue_style( 'wp-learn-style' );
}


/**
 * Create an admin page to show the form submissions
 */
add_action( 'admin_menu', 'wp_learn_securitylist_submenu', 11);
function wp_learn_securitylist_submenu() 
{
    add_menu_page(
        esc_html__('Submissions', 'wp_learn' ),
        esc_html__('Submissions', 'wp_learn' ),
        'manage_options',
        'wp-learn-admin',
        'submissions_render_list'
    );
}

/**
 * Render the list of form submissions
 */
function submissions_render_list()
{
    $submissions = wp_learn_get_for_submissions();

    ?>
    <div class="admin_form_submission" id="admin_form_submission" style="display:none;">
        <h1>Update submission</h1>
        <form method="POST" id="admin_form_submission">
            <input type="hidden" name="wp_update_learn_form" id="wp_update_learn_form" value="wp_update_learn_form" >
            <?php 
                wp_nonce_field( 'wp_update_form_nonce_action', 'wp_update_form_nonce_field' );
            ?>
            <div>
                <label for="wp_learn_user">User:</label><br>
                <input type="text" id="wp_learn_user" name="user" required>
            </div>
            <div>
                <label for="wp_learn_email">Email:</label><br>
                <input type="email" id="wp_learn_email" name="email" required>
            </div>
            <div>
                <label for="wp_learn_age">Age:</label><br>
                <input type="number" id="wp_learn_age" name="age" required>
            </div>
            <div>
                <br>
                <input type="button" id="update_submission" name="update_submission" value="Update Submission">
            </div>
        </form>
    </div>
    <h2>Total Submissions: <?php echo count( $submissions ); ?></h2>    

    <?php if ( count( $submissions ) > 0 ): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">User</th>
                    <th scope="col">Email</th>
                    <th scope="col">Age</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $submissions as $submission ): ?>
                    <tr>
                        <td><?php echo esc_html( $submission['id'] ); ?></td>
                        <td><?php echo esc_html( $submission['user'] ); ?></td>
                        <td><?php echo esc_html( $submission['email'] ); ?></td>
                        <td><?php echo esc_html( $submission['age'] ); ?></td>
                        <td>
                            <button class="edit-button" data-id="<?php echo (int) $submission["id"] ?>">Edit</button>
                            <button class="delete-submission" data-id="<?php echo (int) $submission["id"] ?>">Delete</button>                            
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No submissions found.</p>   
    <?php endif;     
}

/**
 * Get all form submissions from the database
 */
function wp_learn_get_for_submissions():array
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'form_submissions';

    $results = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
    return $results;
}

/**
 * Process the form submission
 */
add_action( 'wp', 'wp_learn_maybe_process_form' );
function wp_learn_maybe_process_form()
{        

    if ( !isset($_POST['wp_learn_form']))
    {                
        return;
    }
    
    
    if ( ! wp_verify_nonce( $_POST['wp_learn_form_nonce_field'], 'wp_learn_form_nonce_action' ) )        
    {
        wp_redirect( 'WPLEARN_ERROR_PAGE_SLUG' );
        die();
    }

    $user  = sanitize_text_field( $_POST['user'] );
    $email = sanitize_email( $_POST['email'] );
    $age = sanitize_text_field( $_POST['age'] );    

    global $wpdb;
    $table_name = $wpdb->prefix . 'form_submissions';

    //Using $wpdb->insert to prevent SQL injection
    $result = $wpdb->insert( 
        $table_name, 
        array( 
            'user' => $user, 
            'email' => $email,
            'age' => $age
        ) 
    );

    /*
    $sql = "INSERT INTO $table_name (user, email, age) VALUES ('$user', '$email', '$age')";
    $result = $wpdb->query( $sql );
    */

    if( 0 < $result )
    {

        wp_redirect( WPLEARN_SUCCESS_PAGE_SLUG );
        die();
    }
    
    wp_redirect( WPLEARN_ERROR_PAGE_SLUG );
    die();  
}

/**
 * Shortcode to display the form
 */
add_shortcode( 'wp_learn_form_short_code', 'wp_learn_form_short_code' );
function wp_learn_form_short_code()
{
    ob_start();
    ?>
    <form method="POST"> 
        <input type="hidden" name="wp_learn_form" value="wp_learn_form">           
        <?php 
            wp_nonce_field( 'wp_learn_form_nonce_action', 'wp_learn_form_nonce_field' );
        ?>
        <div>
            <label for="wp-learn-name">User:</label><br>
            <input type="text" id="wp-learn-name" name="user" required>
        </div>
        <div>
            <label for="wp-learn-email">Email:</label><br>
            <input type="email" id="wp-learn-email" name="email" required>
        </div>
        <div>
            <label for="wp-learn-email">Age:</label><br>
            <input type="number" id="wp-learn-age" name="age" required>
        </div>
        <div>
            <input type="submit" id="submit" name="submit" value="Submit">
        </div>
    </form>
    <?php
    
    $form = ob_get_clean();
    return $form;
}

/**
 * AJAX handler to delete a form submission
 */
add_action( 'wp_ajax_delete_form_submission', 'wp_learn_delete_form_submission' );
function wp_learn_delete_form_submission()
{
    $id = (int) $_POST['id'];
    if ($id === 0)
    {
        return wp_send_json(array(
            'success' => false,
            'message' => 'Invalid submission ID.'
        ));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'form_submissions';

    $rows_deleted = $wpdb->delete( $table_name, array( 'id' => $id ) );

    if( 0 < $rows_deleted )
    {
        $result = true;
        $message = 'Submission deleted successfully.';
    }else
    {
        $result = false;
        $message = 'Failed to delete submission.';
    }

    /*
    $sql = "DELETE FROM $table_name WHERE id = $id";
    $result = $wpdb->get_results( $sql );
    */


    return wp_send_json(array(
        'success' => $result,
        'message' => $message
    ));
}

/**
 * AJAX handler to get a form submission for editing
 */
add_action( 'wp_ajax_edit_form_submission', 'wp_learn_edit_form_submission' );
function wp_learn_edit_form_submission()
{
    $id = (int) $_POST['id'];
    if ($id === 0)
    {
        return wp_send_json(array(
            'success' => false,
            'message' => 'Invalid submission ID.'
        ));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'form_submissions';
    $submission = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );
    if ( null === $submission )
    {
        return wp_send_json(array(
            'success' => false,
            'message' => 'Submission not found.'
        ));
    }

    return wp_send_json(array(
        'success' => true,
        'data' => $submission
    ));
}

/**
 * AJAX handler to update a form submission
 */
add_action( 'wp_ajax_update_form_submission', 'wp_learn_update_form_submission' );
function wp_learn_update_form_submission()
{
    if ( !isset($_POST['identifier']))
    {                
        return;
    }
    
    
    if ( ! wp_verify_nonce( $_POST['hash'], 'wp_update_form_nonce_action' ) )        
    {
        wp_redirect( 'WPLEARN_ERROR_PAGE_SLUG' );
        die();
    }

    $id = (int) $_POST['id'];
    if ($id === 0)
    {
        return wp_send_json(array(
            'success' => false,
            'message' => 'Invalid submission ID.'
        ));
    }

    $user  = sanitize_text_field( $_POST['user'] );
    $email = sanitize_email( $_POST['email'] );
    $age = sanitize_text_field( $_POST['age'] );    

    global $wpdb;
    $table_name = $wpdb->prefix . 'form_submissions';

    $rows_updated = $wpdb->update( 
        $table_name, 
        array( 
            'user' => $user, 
            'email' => $email,
            'age' => $age
        ),
        array( 'id' => $id )
    );

    if( 0 < $rows_updated )
    {
        $result = true;
        $message = 'Submission updated successfully.';
    }else
    {
        $result = false;
        $message = 'Failed to update submission.';
    }

    return wp_send_json(array(
        'success' => $result,
        'message' => $message
    ));
}

?>