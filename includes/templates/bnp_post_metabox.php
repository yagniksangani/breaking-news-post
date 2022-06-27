<?php
/**
 * This template file used for load the html of breaking news section meta fields
 *
 * @package BNP
 */

?>
<style type="text/css">
	.bnp_meta_display_table input.form-control{
		width: 300px;
	}
	.bnp_meta_display_table tr td{
		padding: 5px 15px 15px 0px; 
		width: 250px;
	}
</style>
<div class="bnp-metabox-container">
	<table class="bnp_meta_display_table">
		<tr class="form-group">
			<td><label for="bnp_breaking_news_enable"><?php echo esc_html( __( 'Make this post breaking news', 'bnp' ) ); ?></label></td>
			<?php
				$checked = '';
			if ( $bnp_enable == 'on' ) {
				$checked = 'checked="checked"';
			}
			?>
			<td><input type="checkbox" id="bnp_breaking_news_enable" name="bnp_breaking_news_enable" <?php echo $checked; ?>></td>
		</tr>

		<tr class="form-group">
			<td><label for="bnp_breaking_news_custom_title"><?php echo esc_html( __( 'Custom Title', 'bnp' ) ); ?></label></td>
			<td><input type="text" class="form-control" id="bnp_breaking_news_custom_title" name="bnp_breaking_news_custom_title" value="<?php echo esc_html( $bnp_custom_title ); ?>"></td>
		</tr>

		<tr>
			<td><label for="bnp_breaking_news_expiration_time"><?php echo esc_html( __( 'Expiration Time', 'bnp' ) ); ?></label></td>
			<td><input type="datetime-local" class="form-control" id="bnp_breaking_news_expiration_time" name="bnp_breaking_news_expiration_time" value="<?php echo esc_html( $bnp_expiration_time ); ?>"></td>
			<td><small>Expiration date/time should be based on server universal time: <strong><?php echo gmdate( 'Y-m-d H:i:s' ); ?></strong>.</small></td>
		</tr>
	</table>
</div>
