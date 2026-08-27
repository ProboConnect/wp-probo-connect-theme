<?php
/**
 * Comments.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section class="mt-12 max-w-[760px]" id="comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="mb-6 text-2xl font-extrabold tracking-[-0.02em]">
			<?php
			printf(
				/* translators: %s: comment count. */
				esc_html( _n( '%s comment', '%s comments', get_comments_number(), 'probo-connect-theme' ) ),
				esc_html( number_format_i18n( get_comments_number() ) )
			);
			?>
		</h2>

		<ol class="m-0 flex list-none flex-col gap-6 p-0">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
					'avatar_size' => 40,
				)
			);
			?>
		</ol>

		<?php the_comments_pagination(); ?>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_submit' => 'pp-btn-accent',
			'title_reply'  => __( 'Leave a comment', 'probo-connect-theme' ),
		)
	);
	?>
</section>
