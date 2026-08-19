<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    $categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;
    $availableOnly = filter_var($_GET['available'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $query = trim(mb_substr((string)($_GET['q'] ?? ''), 0, 100));

    $sql = '
        SELECT
            m.id,
            m.category_id,
            m.name_fa,
            m.name_en,
            m.slug,
            m.short_description,
            m.price,
            m.old_price,
            m.discount_percent,
            m.main_image,
            m.calories,
            m.availability,
            m.is_featured,
            m.is_new,
            m.is_bestseller,
            m.is_vegetarian,
            m.is_spicy,
            c.name_fa AS category_name,
            GROUP_CONCAT(DISTINCT CONCAT(t.title, "|", COALESCE(t.color, "#988C75")) ORDER BY t.id SEPARATOR ";;") AS tags
        FROM menu_items m
        INNER JOIN categories c ON c.id = m.category_id
        LEFT JOIN item_tags it ON it.item_id = m.id
        LEFT JOIN tags t ON t.id = it.tag_id AND t.status = "active" AND t.deleted_at IS NULL
        WHERE m.status = "active"
          AND m.deleted_at IS NULL
          AND c.status = "active"
          AND c.deleted_at IS NULL
    ';

    $parameters = [];

    if ($categoryId > 0) {
        $sql .= ' AND m.category_id = :category_id';
        $parameters['category_id'] = $categoryId;
    }

    if ($availableOnly) {
        $sql .= ' AND m.availability = "available"';
    }

    if ($query !== '') {
        $sql .= ' AND (
            m.name_fa LIKE :search
            OR m.name_en LIKE :search
            OR m.short_description LIKE :search
            OR c.name_fa LIKE :search
        )';
        $parameters['search'] = '%' . $query . '%';
    }

    $sql .= '
        GROUP BY
            m.id, m.category_id, m.name_fa, m.name_en, m.slug, m.short_description,
            m.price, m.old_price, m.discount_percent, m.main_image, m.calories,
            m.availability, m.is_featured, m.is_new, m.is_bestseller,
            m.is_vegetarian, m.is_spicy, c.name_fa
        ORDER BY m.sort_order ASC, m.id DESC
        LIMIT 200
    ';

    $statement = db()->prepare($sql);
    $statement->execute($parameters);
    $items = $statement->fetchAll();

    foreach ($items as &$item) {
        $tagList = [];

        if (!empty($item['tags'])) {
            foreach (explode(';;', (string)$item['tags']) as $tag) {
                [$title, $color] = array_pad(explode('|', $tag, 2), 2, '#988C75');
                $tagList[] = ['title' => $title, 'color' => $color];
            }
        }

        $item['id'] = (int)$item['id'];
        $item['category_id'] = (int)$item['category_id'];
        $item['price'] = (float)$item['price'];
        $item['old_price'] = $item['old_price'] !== null ? (float)$item['old_price'] : null;
        $item['discount_percent'] = $item['discount_percent'] !== null ? (float)$item['discount_percent'] : null;
        $item['calories'] = $item['calories'] !== null ? (int)$item['calories'] : null;
        $item['image_url'] = url((string)$item['main_image']);
        $item['detail_url'] = url('item.php?id=' . $item['id']);
        $item['tags'] = $tagList;
        $item['formatted_price'] = format_price($item['price']);
        $item['formatted_old_price'] = $item['old_price'] !== null ? format_price($item['old_price']) : null;
        $item['flags'] = [
            'featured' => (bool)$item['is_featured'],
            'new' => (bool)$item['is_new'],
            'bestseller' => (bool)$item['is_bestseller'],
            'vegetarian' => (bool)$item['is_vegetarian'],
            'spicy' => (bool)$item['is_spicy'],
        ];

        unset(
            $item['is_featured'],
            $item['is_new'],
            $item['is_bestseller'],
            $item['is_vegetarian'],
            $item['is_spicy']
        );
    }
    unset($item);

    json_response([
        'success' => true,
        'count' => count($items),
        'items' => $items,
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => config('app.debug') ? $exception->getMessage() : 'دریافت منو با خطا روبه‌رو شد.',
    ], 500);
}
