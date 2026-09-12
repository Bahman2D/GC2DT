<?php
/* PillarProducts/Logic.php v3.2 */
declare(strict_types=1);

namespace TWODT\Modules\PillarProducts;

defined("ABSPATH") || exit();

use TWODT\Helpers\Assets as Asset;
use TWODT\Helpers\Modules as Mod;

/**
 * پیلارِ کالا — لِی‌اوت جدا، متن‌محور.
 *
 * `Layout/view-pillar.php` و `Router` دست نمی‌خورند: مسیر تازه از همان نقطه‌ی
 * توسعه‌ای گرفته می‌شود که راوتر برای همین گذاشته — فیلتر `twodt_route` و
 * اکشن `twodt/route/{route}`.
 *
 * کالا لابه‌لای متن با شورت‌کد می‌آید، نه شبکه‌ی ته صفحه:
 *   [kala cat="shelter-sleep" limit="3"]
 *   [kala ids="2041,1997" note="دو چادر گتردار"]
 */
final class Logic
{
  public const ROUTE = "pillar-shop";
  public const TAG = "kala";
  public const META_ON = "_twodt_pillar_shop";

  private const MAX = 8;

  public static function register(): void
  {
    add_shortcode(self::TAG, [self::class, "shortcode"]);
    add_filter("twodt_route", [self::class, "route"]);
    add_action("twodt/route/" . self::ROUTE, [self::class, "render"]);
  }

  public static function is_ours(int $post_id = 0): bool
  {
    $post_id = $post_id > 0 ? $post_id : (int) get_queried_object_id();
    if ($post_id <= 0 || get_post_type($post_id) !== "page") {
      return false;
    }

    return get_post_meta($post_id, self::META_ON, true) === "1";
  }

  public static function route(string $route): string
  {
    return is_page() && self::is_ours() ? self::ROUTE : $route;
  }

  public static function render(): void
  {
    Mod::part("PillarProducts/view-layout");
  }

  /** @param array<string,string>|string $atts */
  public static function shortcode($atts = []): string
  {
    if (!class_exists(\WooCommerce::class)) {
      return "";
    }

    $a = shortcode_atts(
      ["cat" => "", "ids" => "", "limit" => "", "note" => "", "more" => ""],
      is_array($atts) ? $atts : [],
      self::TAG,
    );

    /* limit که داده نشده باشد: فهرست دستی همان‌قدر که هست، وگرنه سه‌تا.
       وگرنه ids="a,b,c,d" بی‌صدا به سه قلم بریده می‌شد. */
    $given = trim((string) $a["limit"]);
    $picked_n = count(preg_split('/[^\d]+/', (string) $a["ids"], -1, PREG_SPLIT_NO_EMPTY) ?: []);
    $limit = $given !== ""
      ? (int) $given
      : ($picked_n > 0 ? $picked_n : 3);
    $limit = max(1, min(self::MAX, $limit));
    $ids = self::resolve((string) $a["ids"], (string) $a["cat"], $limit);
    if ($ids === []) {
      return "";
    }

    self::assets();

    ob_start();
    Mod::part("PillarProducts/view", "", [
      "ids" => $ids,
      "note" => (string) $a["note"],
      "more" => self::more_url((string) $a["more"], (string) $a["cat"]),
    ]);

    return trim((string) ob_get_clean());
  }

  public static function assets(): void
  {
    $css = Asset::minified(Mod::rel("PillarProducts/view.css"));
    if ($css !== "") {
      wp_enqueue_style("twodt-pillar-products", Asset::uri($css), [], Asset::ver($css));
    }
  }

  /** @return array<int,int> */
  private static function resolve(string $ids_raw, string $cat_raw, int $limit): array
  {
    $picked = [];
    foreach (preg_split('/[^\d]+/', $ids_raw, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $chunk) {
      $id = (int) $chunk;
      if ($id > 0 && get_post_type($id) === "product" && get_post_status($id) === "publish") {
        $picked[] = $id;
      }
    }

    $slugs = array_values(array_filter(array_map("trim", explode(",", $cat_raw))));
    if ($slugs !== [] && count($picked) < $limit) {
      $args = [
        "post_type" => "product",
        "post_status" => "publish",
        "posts_per_page" => $limit - count($picked),
        "fields" => "ids",
        "ignore_sticky_posts" => true,
        "post__not_in" => $picked,
        "tax_query" => [
          [
            "taxonomy" => "product_cat",
            "field" => "slug",
            "terms" => $slugs,
            "include_children" => true,
          ],
        ],
        "meta_query" => [["key" => "_stock_status", "value" => "instock"]],
        /* پرفروش اول؛ فروشگاه تازه که آمار ندارد، تازه‌ترین‌ها بالا می‌آیند.
           ترتیب الفبایی بدترین حالت است — لوازم جانبی سرِ فهرست می‌نشینند. */
        "meta_key" => "total_sales",
        "orderby" => ["meta_value_num" => "DESC", "date" => "DESC"],
      ];
      $found = get_posts($args);
      if ($found === []) {
        unset($args["meta_query"]);
        $found = get_posts($args);
      }
      $picked = array_merge($picked, $found);
    }

    return array_slice(array_values(array_unique(array_map("intval", $picked))), 0, $limit);
  }

  private static function more_url(string $more, string $cat_raw): string
  {
    if ($more !== "") {
      /* نشانی نسبی هم پذیرفته می‌شود — نوشتن /shop/... در متن راحت‌تر از آدرس کامل است */
      if (filter_var($more, FILTER_VALIDATE_URL)) {
        return $more;
      }
      if (str_starts_with($more, "/")) {
        return home_url($more);
      }
    }
    $slugs = array_values(array_filter(array_map("trim", explode(",", $cat_raw))));
    if (count($slugs) !== 1) {
      return "";
    }
    $term = get_term_by("slug", $slugs[0], "product_cat");
    if (!$term instanceof \WP_Term) {
      return "";
    }
    $link = get_term_link($term);

    return is_string($link) ? $link : "";
  }

  /** یک مشخصه‌ی کوتاه زیر نام کالا — همان چیزی که خریدار با آن مقایسه می‌کند */
  public static function hint(int $product_id): string
  {
    $specs = json_decode((string) get_post_meta($product_id, "_twodt_specs", true), true);
    $cat = json_decode((string) get_post_meta($product_id, "_twodt_specs_catalog", true), true);
    $all = array_merge(is_array($specs) ? $specs : [], is_array($cat) ? $cat : []);
    foreach (["تعداد نفر", "ظرفیت", "لومن", "ستون آب", "وزن", "جنس"] as $key) {
      $val = trim((string) ($all[$key] ?? ""));
      if ($val !== "") {
        return $val;
      }
    }

    return "";
  }
}
