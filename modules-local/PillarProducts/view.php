<?php
/* PillarProducts/view.php v4.4 */
declare(strict_types=1);

defined("ABSPATH") || exit();

use TWODT\Modules\PillarProducts\Logic;

$ids = isset($args["ids"]) && is_array($args["ids"]) ? $args["ids"] : [];
$note = isset($args["note"]) ? (string) $args["note"] : "";
$more = isset($args["more"]) ? (string) $args["more"] : "";

if ($ids === []) {
  return;
}
$label = $note !== "" ? $note : __("کالاهای مرتبط", "twodt");
?>
<aside class="kala" aria-label="<?php echo esc_attr($label); ?>">
  <ul class="kala__list">

    <li class="kala__item kala__item--lead">
      <div class="kala__lead">
        <p class="kala__lead-title"><?php echo esc_html($label); ?></p>
        <?php if ($more !== ""): ?>
          <a class="kala__lead-link" href="<?php echo esc_url($more); ?>">
            <?php esc_html_e("دیدن همه", "twodt"); ?>
            <svg viewBox="0 -960 960 960" width="15" height="15" aria-hidden="true" focusable="false">
              <path d="M560-280v-400L360-480l200 200Z"></path>
            </svg>
          </a>
        <?php endif; ?>
      </div>
    </li>

    <?php foreach ($ids as $pid):
      $pid = (int) $pid;
      $product = wc_get_product($pid);
      if (!$product instanceof WC_Product) {
        continue;
      }
      $hint = Logic::hint($pid);
      /* کالای ناموجود قیمت نشان نمی‌دهد — نشان «ناموجود» روی عکس گویاست */
      $price = $product->is_in_stock() ? $product->get_price_html() : "";
      $off = 0;
      $reg = (float) $product->get_regular_price();
      $now = (float) $product->get_price();
      if ($reg > 0 && $now > 0 && $now < $reg) {
        $off = (int) round((($reg - $now) / $reg) * 100);
      }
    ?>
      <li class="kala__item">
        <a class="kala__link" href="<?php echo esc_url((string) get_permalink($pid)); ?>">
          <span class="kala__media">
            <?php echo $product->get_image("woocommerce_thumbnail", ["loading" => "lazy", "class" => "kala__img"]); ?>
            <?php if (!$product->is_in_stock()): ?>
              <span class="kala__flag kala__flag--out"><?php esc_html_e("ناموجود", "twodt"); ?></span>
            <?php elseif ($off > 0): ?>
              <span class="kala__flag kala__flag--off"><?php echo esc_html(number_format_i18n($off)); ?>٪</span>
            <?php endif; ?>
            <?php if ($hint !== ""): ?>
              <span class="kala__hint"><?php echo esc_html($hint); ?></span>
            <?php endif; ?>
          </span>

          <span class="kala__body">
            <span class="kala__name"><?php echo esc_html($product->get_name()); ?></span>
            <?php if ($price !== "" && $price !== null): ?>
              <span class="kala__price"><?php echo wp_kses_post($price); ?></span>
            <?php endif; ?>
          </span>
        </a>
      </li>
    <?php endforeach; ?>

  </ul>
</aside>
