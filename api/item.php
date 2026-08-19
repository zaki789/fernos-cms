<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use Fernosa\Services\VisitService;

try {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        json_response(['success' => false, 'message' => 'شناسه آیتم معتبر نیست.'], 422);
    }

    $statement = db()->prepare(
        'SELECT
            m.*,
            c.name_fa AS category_name,
            GROUP_CONCAT(DISTINCT CONCAT(t.title, "|", COALESCE(t.color, "#988C75")) ORDER BY t.id SEPARATOR ";;") AS tags
         FROM menu_items m
         INNER JOIN categories c ON c.id = m.category_id
         LEFT JOIN item_tags it ON it.item_id = m.id
         LEFT JOIN tags t ON t.id = it.tag_id AND t.status = "active" AND t.deleted_at IS NULL
         WHERE m.id = :id
           AND m.status = "active"
           AND m.deleted_at IS NULL
         GROUP BY m.id, c.name_fa
         LIMIT 1'
    );
    $statement->execute(['id' => $id]);
    $item = $statement->fetch();

    if (!$item) {
        json_response(['success' => false, 'message' => 'آیتم موردنظر پیدا نشد.'], 404);
    }

    db()->prepare('UPDATE menu_items SET view_count = view_count + 1 WHERE id = :id')
        ->execute(['id' => $id]);
    VisitService::track('item', (int)$id);

    $tags = [];
    if (!empty($item['tags'])) {
        foreach (explode(';;', (string)$item['tags']) as $tag) {
            [$title, $color] = array_pad(explode('|', $tag, 2), 2, '#988C75');
            $tags[] = ['title' => $title, 'color' => $color];
        }
    }

    $relatedStatement = db()->prepare(
        'SELECT id, name_fa, name_en, main_image, price
         FROM menu_items
         WHERE category_id = :category_id
           AND id <> :id
           AND status = "active"
           AND deleted_at IS NULL
         ORDER BY is_featured DESC, sort_order ASC
         LIMIT 3'
    );
    $relatedStatement->execute([
        'category_id' => $item['category_id'],
        'id' => $id,
    ]);
    $related = $relatedStatement->fetchAll();

    foreach ($related as &$relatedItem) {
        $relatedItem['id'] = (int)$relatedItem['id'];
        $relatedItem['image_url'] = url((string)$relatedItem['main_image']);
        $relatedItem['formatted_price'] = format_price($relatedItem['price']);
    }
    unset($relatedItem);

    json_response([
        'success' => true,
        'item' => [
            'id' => (int)$item['id'],
            'name_fa' => $item['name_fa'],
            'name_en' => $item['name_en'],
            'category_name' => $item['category_name'],
            'short_description' => $item['short_description'],
            'full_description' => $item['full_description'],
            'price' => (float)$item['price'],
            'old_price' => $item['old_price'] !== null ? (float)$item['old_price'] : null,
            'formatted_price' => format_price($item['price']),
            'formatted_old_price' => $item['old_price'] !== null ? format_price($item['old_price']) : null,
            'image_url' => url((string)$item['main_image']),
            'ingredients' => $item['ingredients_text'],
            'calories' => $item['calories'] !== null ? (int)$item['calories'] : null,
            'allergens' => $item['allergens_text'],
            'availability' => $item['availability'],
            'tags' => $tags,
            'share_url' => url('item.php?id=' . $item['id']),
            'related' => $related,
        ],
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => config('app.debug') ? $exception->getMessage() : 'دریافت جزئیات آیتم با خطا روبه‌رو شد.',
    ], 500);
}
