jQuery( document ).ready(
    
	function ($) {
		console.log( 'WP Learn Plugin Security admin.js loaded' );
        const updateSubmissionButton = document.getElementById( 'update_submission' );
        const formEditSubmission = document.getElementById( 'admin_form_submission' );
        let submissionId = 0;
        let identifier = document.getElementById( 'wp_update_learn_form' );
        let hasIdentifier = document.getElementById( 'wp_update_form_nonce_field' );
        let user = document.getElementById( 'wp_learn_user' );
        let email = document.getElementById( 'wp_learn_email' );
        let age = document.getElementById( 'wp_learn_age' );

		$( '.delete-submission' ).on(
			'click',
			function (event) {
				console.log( 'Delete button clicked' );
				var this_button = $( this );
				event.preventDefault();
				var id = this_button.data( 'id' );
				console.log( 'Delete submission id ' + id );
				jQuery.post(
					wp_learn_ajax.ajax_url,
					{
						'action': 'delete_form_submission',
						'id': id,
					},
					function (response) {
						console.log( response );
						alert( `${response.message}` );
						document.location.reload();
					}
				);
			}
		);
        $( '.edit-button' ).on(
            'click',
            function (event) {                
                console.log( 'Edit button clicked' );
                var this_button = $( this );
                event.preventDefault();
                submissionId = this_button.data( 'id' );
                console.log( 'Edit submission id ' + submissionId );
                jQuery.post(
                    wp_learn_ajax.ajax_url,
                    {
                        'action': 'edit_form_submission',
                        'id': submissionId,
                    },
                    function (response) {       
                        if(response.success)
                        {
                            fillForm(response.data); 
                            formEditSubmission.style.display = 'block';
                        }
                        else
                        {
                            alert( `${response.data}` );
                        }                                                                    
                    }                    

                )

            }
        );


        function updateSubmission()
        {
            jQuery.post(
                wp_learn_ajax.ajax_url,
                {
                    'action': 'update_form_submission',
                    'identifier': identifier.value,
                    'hash': hasIdentifier.value,
                    'id': submissionId,
                    'user': user.value,
                    'email': email.value,
                    'age': age.value,
                },
                function (response) {
                    console.log( response );
                    alert( `${response.message}` );
                    //document.location.reload();
                }
            );
        }

        if (updateSubmissionButton) 
        {    
            updateSubmissionButton.addEventListener( 'click', function () {
                console.log('Update submission button clicked');    
                if ( user.value === '' || email.value === '' || age.value === '' )
                {
                    alert( 'Por favor rellene los campos.' );
                    return;
                } 
                
                if ( submissionId > 0 )
                {
                    console.log('Updating submission ID: ' + submissionId);
                    updateSubmission();
                }
            });
        }

        

        function fillForm(submission)
        {

            $('#wp_learn_user').val ? $('#wp_learn_user').val(submission.user) : '';
            $('#wp_learn_email').val ? $('#wp_learn_email').val(submission.email) : '';
            $('#wp_learn_age').val ? $('#wp_learn_age').val(submission.age) : '';            
            
        }
	}
);