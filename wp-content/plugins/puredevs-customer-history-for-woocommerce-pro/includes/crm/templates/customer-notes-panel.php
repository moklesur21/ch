<?php
/**
 * Customer notes panel (R15-05).
 *
 * Required from PDWCHS_Customer_Notes::pdwchs_render_notes_panel().
 * In scope: $notes (array of row objects), $count (int), $user_id (int).
 *
 * @since 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="pd-ch-panel pdwchs-notes-panel">
	<h2>
		<?php esc_html_e( 'Notes', 'puredevs-customer-history-for-woocommerce' ); ?>
		<span class="pdwchs-notes-count"><?php echo esc_html( $count ); ?></span>
	</h2>

	<div class="pdwchs-notes-add">
		<textarea class="pdwchs-note-input" placeholder="<?php esc_attr_e( 'Add a note about this customer...', 'puredevs-customer-history-for-woocommerce' ); ?>"></textarea>
		<button type="button" class="button button-primary pdwchs-note-add-btn"><?php esc_html_e( 'Add Note', 'puredevs-customer-history-for-woocommerce' ); ?></button>
		<div class="pdwchs-crm-error" style="display:none;"></div>
	</div>

	<ul class="pdwchs-notes-list">
		<?php foreach ( $notes as $note ) : ?>
			<?php
			$author      = get_userdata( $note->created_by );
			$author_name = $author
				? $author->display_name
				/* translators: %d: WP user id of the deleted note author. */
				: sprintf( __( 'Deleted user (#%d)', 'puredevs-customer-history-for-woocommerce' ), $note->created_by );
			$timestamp   = strtotime( $note->created_at );
			?>
			<li class="pdwchs-note-row" data-note-id="<?php echo esc_attr( $note->id ); ?>">
				<div class="pdwchs-note-meta">
					<span class="pdwchs-note-author"><?php echo esc_html( $author_name ); ?></span>
					<span class="pdwchs-note-date" title="<?php echo esc_attr( human_time_diff( $timestamp, current_time( 'timestamp' ) ) ); ?>">
						<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) ); ?>
					</span>
					<button type="button" class="pdwchs-note-delete" aria-label="<?php esc_attr_e( 'Delete note', 'puredevs-customer-history-for-woocommerce' ); ?>">&times;</button>
				</div>
				<div class="pdwchs-note-body"><?php echo nl2br( esc_html( $note->note_text ) ); ?></div>
			</li>
		<?php endforeach; ?>
	</ul>

	<p class="pdwchs-notes-empty" <?php echo empty( $notes ) ? '' : 'style="display:none;"'; ?>>
		<?php esc_html_e( 'No notes yet. Add one above.', 'puredevs-customer-history-for-woocommerce' ); ?>
	</p>
</section>
