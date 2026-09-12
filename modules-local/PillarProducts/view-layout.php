<?php
/* PillarProducts/view-layout.php v1.0 */
declare(strict_types=1);
defined("ABSPATH") || exit();

use TWODT\Helpers\Modules as Mod;
use TWODT\Modules\Layout\Logic as LayoutLogic;

ob_start();
get_header();
Mod::part("HeroPage/view");
?>
<main class="m page-layout mx-auto pb-3 px-3 lg:max-w-225 md:px-5 2xl:max-w-full">
  <?php Mod::part("Breadcrumbs/view"); ?>
  <?php do_action("twodt/layout/slot", "top", (int) get_queried_object_id(), "pillar-shop"); ?>

  <?php if (have_posts()) {
    while (have_posts()) {
      the_post();
      ?>
      <article class="c gap-3" <?php post_class(); ?>>

        <?php if (LayoutLogic::has_toc()) : ?>
          <?php Mod::part("TOC/view", "collapsible"); ?>
        <?php endif; ?>

        <?php do_action("twodt/layout/slot", "before_content", (int) get_the_ID(), "pillar-shop"); ?>

        <div class="c-m px-3 prose text-justify bg-(--bg-secondary) rounded-lg border border-(--border-light) md:px-5">
          <?php the_content(); ?>
        </div>

        <?php if (LayoutLogic::has_buy()) : ?>
          <?php Mod::part("Buy/view"); ?>
        <?php endif; ?>

        <?php Mod::part("CTA/view"); ?>

        <?php do_action("twodt/layout/slot", "after_content", (int) get_the_ID(), "pillar-shop"); ?>

        <?php if (LayoutLogic::has_faq()) : ?>
          <?php Mod::part("FAQ/view"); ?>
        <?php endif; ?>

        <?php comments_template(); ?>
      </article>
      <?php
    }
  } ?>

  <?php get_sidebar(); ?>
</main>
<?php
get_footer();

$html = ob_get_clean();
if (class_exists(\TWODT\Modules\TOC\Logic::class)) {
  echo \TWODT\Modules\TOC\Logic::processPageHTML($html);
} else {
  echo $html;
}
