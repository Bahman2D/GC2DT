<?php
/* PillarProducts/view.php v6.0 */
declare(strict_types=1);

defined("ABSPATH") || exit();

use TWODT\Helpers\Modules as Mod;
use TWODT\Modules\PillarProducts\Logic;

$ids = isset($args["ids"]) && is_array($args["ids"]) ? $args["ids"] : [];
$note = isset($args["note"]) ? (string) $args["note"] : "";
$more = isset($args["more"]) ? (string) $args["more"] : "";

if ($ids === []) {
  return;
}
$label = $note !== "" ? $note : __("کالاهای مرتبط", "twodt");
?>
<aside class="f-deal min-w-0" aria-label="<?php echo esc_attr($label); ?>">
  <div class="f-deal__box">
    <div class="f-deal__lock">
      <div class="f-deal__title"><?php echo esc_html($label); ?></div>
      <?php if ($more !== ""): ?>
        <a class="f-deal__more" href="<?php echo esc_url($more); ?>">
          <?php esc_html_e("مشاهده همه", "twodt"); ?>
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m15 6-6 6 6 6"/></svg>
        </a>
      <?php endif; ?>
    </div>
    <div class="f-deal__body">
      <div class="f-deal__panel">
        <ul class="products p-strip__list f-deal__list" data-scroll-strip="arrows">
          <?php
          global $post, $product;
          $keep_post = $post;
          $keep_product = $product;
          foreach ($ids as $pid) {
            $pid = (int) $pid;
            $item = wc_get_product($pid);
            if (!$item instanceof WC_Product) {
              continue;
            }
            $post = get_post($pid);
            setup_postdata($post);
            $product = $item;
            Mod::part("Shop/view-card", null, [
              "actions" => false,
              "hint" => Logic::hint($pid),
              "title_tag" => "span",
            ]);
          }
          $post = $keep_post;
          $product = $keep_product;
          wp_reset_postdata();
          ?>
        </ul>
      </div>
    </div>
  </div>
</aside>
