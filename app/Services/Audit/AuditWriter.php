<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** 寫入稽核紀錄並維護雜湊鏈（WP0.3）。 */
class AuditWriter
{
    /** @var array<int, string> */
    private array $redacted;

    public function __construct()
    {
        $this->redacted = config('carbon.audit.redacted_fields', []);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(string $event, ?Model $subject = null, ?array $before = null, ?array $after = null, ?User $actor = null, ?string $ip = null, ?string $userAgent = null): AuditLog
    {
        $occurredAt = now();
        $prevHash = AuditLog::query()->orderByDesc('id')->value('hash');

        $payload = json_encode([
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'actor_id' => $actor?->getKey(),
            'before' => $this->redact($before),
            'after' => $this->redact($after),
            'occurred_at' => $occurredAt->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return AuditLog::create([
            'occurred_at' => $occurredAt,
            'actor_id' => $actor?->getKey(),
            'event' => $event,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'before' => $this->redact($before),
            'after' => $this->redact($after),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'prev_hash' => $prevHash,
            'hash' => hash(config('carbon.audit.hash_algo', 'sha256'), ($prevHash ?? '') . $payload),
        ]);
    }

    /**
     * 驗證整條雜湊鏈；回傳第一筆被竄改的紀錄 id，全部正常時回傳 null。
     */
    public function verifyChain(): ?int
    {
        $prev = null;

        foreach (AuditLog::query()->orderBy('id')->cursor() as $log) {
            $payload = json_encode([
                'event' => $log->event,
                'subject_type' => $log->auditable_type,
                'subject_id' => $log->auditable_id,
                'actor_id' => $log->actor_id,
                'before' => $log->before,
                'after' => $log->after,
                'occurred_at' => $log->occurred_at?->toIso8601String(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $expected = hash(config('carbon.audit.hash_algo', 'sha256'), ($prev ?? '') . $payload);

            if ($log->prev_hash !== $prev || ! hash_equals($expected, $log->hash)) {
                return $log->id;
            }

            $prev = $log->hash;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>|null
     */
    private function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        foreach ($this->redacted as $field) {
            if (array_key_exists($field, $values)) {
                $values[$field] = '[redacted]';
            }
        }

        return $values;
    }
}
