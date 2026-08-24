<?php
/**
 * Single post.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main class="pp-container py-14">
		<article>
			<div class="pp-eyebrow text-ink-4"><?php echo esc_html( get_the_date() ); ?></div>
			<h1 class="mt-3 mb-6 max-w-[820px] text-4xl font-extrabold tracking-[-0.035em] lg:text-5xl"><?php the_title(); ?></h1>

			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'large', array( 'class' => 'rounded-pp mb-8 w-full object-cover' ) ); ?>
			<?php endif; ?>

			<div class="prose-pp max-w-[760px] text-[17px] leading-relaxed text-ink-2"><?php the_content(); ?></div>
		</article>

		<?php
		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
		?>
	</main>
	<?php
endwhile;

get_footer();
