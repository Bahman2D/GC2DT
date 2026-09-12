<?php
/* PillarProducts/MetaBox.php v2.0 */
declare(strict_types=1);

namespace TWODT\Modules\PillarProducts;

defined("ABSPATH") || exit();

final class MetaBox
{
  private const NONCE_NAME = "twodt_pillar_shop_nonce";
  private const NONCE_ACTION = "twodt_pillar_shop_save";

  public static function register(): void
  {
    add_action("add_meta_boxes_page", [self::class, "add_meta_box"], 10, 1);
    add_action("save_post_page", [self::class, "save_meta"], 10, 2);
  }

  public static function add_meta_box(\WP_Post $post): void
  {
    if ((int) $post->post_parent !== 0) {
      return;
    }

    add_meta_box(
      "twodt-pillar-shop",
      __("پیلار کالا", "twodt"),
      [self::class, "render"],
      "page",
      "side",
      "default",
    );
  }

  public static function render(\WP_Post $post): void
  {
    wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
    $on = get_post_meta((int) $post->ID, Logic::META_ON, true) === "1";
    ?>
    <p>
      <label>
        <input type="checkbox" name="twodt_pillar_shop" value="1" <?php checked($on); ?>>
        <strong><?php esc_html_e("این صفحه با تمپلیت پیلار کالا رندر شود", "twodt"); ?></strong>
      </label>
    </p>
    <p class="description">
      <?php esc_html_e(
        "تمپلیتی متن‌محور: نوشته اصل است و کالاها لابه‌لای متن می‌آیند. هرجای متن که خواستی این را بگذار:",
        "twodt",
      ); ?>
    </p>
    <p><code dir="ltr" style="display:block;padding:6px;background:#f6f7f7">[kala cat="shelter-sleep" limit="3"]</code></p>
    <p class="description">
      <?php esc_html_e("یا کالاهای مشخص:", "twodt"); ?>
    </p>
    <p><code dir="ltr" style="display:block;padding:6px;background:#f6f7f7">[kala ids="2041,1997" note="دو چادر گتردار"]</code></p>
    <p class="description">
      <?php esc_html_e(
        "cat اسلاگ دسته است (با ویرگول چندتا) · ids شماره‌ی محصول · limit پیش‌فرض ۳ · note یک خط توضیح بالای بلوک · more آدرس دلخواه برای «دیدن همه».",
        "twodt",
      ); ?>
    </p>
    <?php
  }

  public static function save_meta(int $post_id, \WP_Post $post): void
  {
    if ($post->post_type !== "page") {
      return;
    }
    if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
      return;
    }
    if (wp_is_post_revision($post_id)) {
      return;
    }
    $nonce = isset($_POST[self::NONCE_NAME]) ? (string) $_POST[self::NONCE_NAME] : "";
    if ($nonce === "" || !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
      return;
    }
    if (!current_user_can("edit_page", $post_id)) {
      return;
    }

    if (isset($_POST["twodt_pillar_shop"])) {
      update_post_meta($post_id, Logic::META_ON, "1");
    } else {
      delete_post_meta($post_id, Logic::META_ON);
    }
  }
}
