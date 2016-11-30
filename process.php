<?php
define( 'WP_USE_THEMES', false );
require_once '../../../wp-load.php';

// Check if the correct variables have been passed
// through, if not, redirect to the homepage

if ( $_GET['wg_action'] == 'process' && is_numeric( $_GET['wg_order_id'] ) && ctype_alnum( $_GET['jg_donation_id'] ) ) {
	// Proceed
} else {
	wp_redirect(home_url());
	exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="robots" content="noindex, nofollow">
<title><?php _e( 'Processing...', 'woogiving' ); ?></title>
<style type="text/css">
body {
	text-align: center;
	font-family: Helvetica, Tahoma, sans-serif;
}
</style>
<script type="text/javascript">
var exitPage = false;
window.onbeforeunload = function() {
	if (!exitPage) {
		return '<?php _e( 'Your donation is still processing, please wait.', 'woogiving' ); ?>';
	}
};
</script>
</head>
<body>

<h1><?php _e( 'Processing...', 'woogiving' ); ?></h1>

<p><img src="<?php echo plugin_dir_url( __FILE__ ) . 'img/loading.gif' ?>" alt="<?php esc_attr_e( 'Please wait.', 'woogiving' ); ?>"></p>

<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="js/vendor/jquery-2.1.4.min.js"><\/script>')</script>
<script type="text/javascript">

jQuery(function($){

	// Process donation with ajax
	
	$.ajax('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
		data: 'action=wg_ajax_process_donation&wg_action=process&wg_order_id=<?php echo urlencode( $_GET['wg_order_id'] ); ?>&jg_donation_id=<?php echo urlencode( $_GET['jg_donation_id'] ); ?>',
		type: 'POST',
		dataType: 'json',
		success: function(resp) {
			exitPage = true;
			if (resp.wg_status == 'success') {
				window.location = resp.wg_redirect;
			} else if (resp.wg_status == 'failure') {
				$('h1').text(resp.wg_message);
				$('p').hide();
			}
		}
	});

});

</script>
</body>

</html>
