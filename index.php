<?php
/**
 * Fallback template: blog index, archives and search results.
 *
 * @package Probo_Connect
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="pp-container py-14">
	<?php if ( have_posts() ) : ?>
		<header class="mb-8">
			<h1 class="text-4xl font-extrabold tracking-[-0.035em] lg:text-5xl">
				<?php
				if ( is_search() ) {
					/* translators: %s: search term. */
					printf( esc_html__( 'Search results for “%s”', 'probo-connect' ), esc_html( get_search_query() ) );
				} elseif ( is_archive() ) {
					the_archive_title();
				} else {
					esc_html_e( 'News', 'probo-connect' );
				}
				?>
			</h1>

			<?php if ( is_archive() ) : ?>
				<div class="mt-3 max-w-[640px] text-[17px] leading-relaxed text-ink-2"><?php the_archive_description(); ?></div>
			<?php endif; ?>
		</header>

		<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article class="pp-card p-6">
					<?php if ( has_post_thumbnail() ) : ?>
						<a class="mb-4 block" href="<?php the_permalink(); ?>">
							<?php the_post_thumbnail( 'medium_large', array( 'class' => 'rounded-pp h-[190px] w-full object-cover' ) ); ?>
						</a>
					<?php endif; ?>

					<div class="pp-eyebrow text-ink-4"><?php echo esc_html( get_the_date() ); ?></div>
					<h2 class="mt-2 text-xl font-bold tracking-[-0.02em]">
						<a class="text-ink no-underline hover:text-accent-ink" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h2>
					<p class="mt-3 text-[15px] leading-relaxed text-ink-2"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
				</article>
			<?php endwhile; ?>
		</div>

		<div class="mt-10">
			<?php the_posts_pagination( array( 'mid_size' => 2 ) ); ?>
		</div>
	<?php else : ?>
		<h1 class="text-4xl font-extrabold tracking-[-0.035em]"><?php esc_html_e( 'Nothing found', 'probo-connect' ); ?></h1>
		<p class="mt-3 max-w-[560px] text-[17px] leading-relaxed text-ink-2">
			<?php esc_html_e( 'Try a different search term, or browse the full range.', 'probo-connect' ); ?>
		</p>
		<div class="mt-6 max-w-[560px]"><?php probo_search_form( 'header' ); ?></div>
	<?php endif; ?>
</main>

<?php
get_footer();
