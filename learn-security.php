<?php
/**
 * Plugin Name: WP Learn Security
 * Description: learn security best practices for WordPress development
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) )
{
    exit; // Exit if accessed directly
}

define('WPLEARN_SUCCESS_PAGE_SLUG', home_url('/success'));
define('WPLEARN_ERROR_PAGE_SLUG', home_url('/error'));

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

function submissions_render_list()
{
    $submissions = wp_learn_get_for_submissions();

    ?>
    <h1>Form Submissions</h1>
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
                            <button>Edit</button>
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

function wp_learn_get_for_submissions():array
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'form_submissions';

    $results = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
    return $results;
}

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

    $sql = "INSERT INTO $table_name (user, email, age) VALUES ('$user', '$email', '$age')";
    $result = $wpdb->query( $sql );

    if( 0 < $result )
    {

        wp_redirect( WPLEARN_SUCCESS_PAGE_SLUG );
        die();
    }
    
    wp_redirect( WPLEARN_ERROR_PAGE_SLUG );
    die();  
}

//The functionalities for frontent form
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

    $sql = "DELETE FROM $table_name WHERE id = $id";
    $result = $wpdb->get_results( $sql );

    return wp_send_json(array(
        'success' => true,
        'message' => 'Submission deleted successfully.'
    ));
}

?>