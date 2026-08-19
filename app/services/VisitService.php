<?php
declare(strict_types=1);

namespace Fernosa\Services;

use Throwable;

final class VisitService
{
    public static function track(string $pageType, ?int $entityId = null, ?int $qrCodeId = null): void
    {
        try {
            $fingerprint = hash(
                'sha256',
                client_ip() . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . date('Y-m-d')
            );

            db()->prepare(
                'INSERT INTO visits
                    (page_type, entity_id, qr_code_id, visitor_hash, referrer, user_agent, visited_at)
                 VALUES
                    (:page_type, :entity_id, :qr_code_id, :visitor_hash, :referrer, :user_agent, NOW())'
            )->execute([
                'page_type' => mb_substr($pageType, 0, 50),
                'entity_id' => $entityId,
                'qr_code_id' => $qrCodeId,
                'visitor_hash' => $fingerprint,
                'referrer' => mb_substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 1000),
                'user_agent' => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
            ]);

            if ($qrCodeId) {
                db()->prepare('UPDATE qr_codes SET scan_count = scan_count + 1, last_scanned_at = NOW() WHERE id = :id')
                    ->execute(['id' => $qrCodeId]);
            }
        } catch (Throwable) {
            // آمار نباید نمایش صفحه را مختل کند.
        }
    }
}
