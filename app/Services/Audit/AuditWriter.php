<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * 寫入稽核紀錄並維護雜湊鏈（WP0.3）。
 *
 * 併發安全：鏈尾放在 audit_chain 的單一列，寫入時在同一交易內用
 * SELECT ... FOR UPDATE 鎖住它，讀到的 prev_hash 到下筆 insert 之間不會被插隊；
 * 交易外層再重試，避免死結直接失敗。SQLite 沒有列鎖，但寫入本來就是整庫序列化。
 *
 * 雜湊內容包含 ip_address 與 user_agent，事後改寫來源位址同樣會斷鏈。
 */
class AuditWriter
{
    private const CHAIN_ID = 1;

    /** @var array<int, string> */
    private array $redacted;

    public function __construct()
    {
        $this->redacted = (array) config('carbon.audit.redacted_fields', []);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        string $event,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null,
        ?User $actor = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): AuditLog {
        $request = app()->bound('request') && ! app()->runningInConsole() ? request() : null;

        $ip ??= $request?->ip();
        $userAgent ??= $request?->userAgent();
        $occurredAt = now()->startOfSecond();

        $before = $this->redact($before);
        $after = $this->redact($after);

        return DB::transaction(function () use ($event, $subject, $before, $after, $actor, $ip, $userAgent, $occurredAt): AuditLog {
            $prevHash = $this->lockChainTail();

            $hash = $this->digest(
                prevHash: $prevHash,
                event: $event,
                subjectType: $subject?->getMorphClass(),
                subjectId: $subject?->getKey(),
                actorId: $actor?->getKey(),
                before: $before,
                after: $after,
                ip: $ip,
                userAgent: $userAgent,
                occurredAt: $occurredAt,
            );

            $log = AuditLog::create([
                'occurred_at' => $occurredAt,
                'actor_id' => $actor?->getKey(),
                'event' => $event,
                'auditable_type' => $subject?->getMorphClass(),
                'auditable_id' => $subject?->getKey(),
                'before' => $before,
                'after' => $after,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'prev_hash' => $prevHash,
                'hash' => $hash,
            ]);

            DB::table('audit_chain')->where('id', self::CHAIN_ID)->update([
                'last_id' => $log->getKey(),
                'last_hash' => $hash,
                'updated_at' => now(),
            ]);

            return $log;
        }, 5);
    }

    /**
     * 驗證整條雜湊鏈；回傳第一筆被竄改的紀錄 id，全部正常時回傳 null。
     * 鏈尾與 audit_chain 不一致時回傳鏈尾 id（0 代表鏈本身還沒建立）。
     */
    public function verifyChain(): ?int
    {
        $prev = null;

        foreach (AuditLog::query()->orderBy('id')->cursor() as $log) {
            if ($log->prev_hash !== $prev) {
                return (int) $log->id;
            }

            $expected = $this->digest(
                prevHash: $prev,
                event: (string) $log->event,
                subjectType: $log->auditable_type,
                subjectId: $log->auditable_id,
                actorId: $log->actor_id,
                before: $log->before,
                after: $log->after,
                ip: $log->ip_address,
                userAgent: $log->user_agent,
                occurredAt: $log->occurred_at,
            );

            if (! hash_equals($expected, (string) $log->hash)) {
                return (int) $log->id;
            }

            $prev = $log->hash;
        }

        $chain = DB::table('audit_chain')->where('id', self::CHAIN_ID)->first();

        if ($chain !== null && $chain->last_hash !== $prev) {
            return (int) ($chain->last_id ?? 0);
        }

        return null;
    }

    private function lockChainTail(): ?string
    {
        $chain = DB::table('audit_chain')->where('id', self::CHAIN_ID)->lockForUpdate()->first();

        if ($chain === null) {
            throw new RuntimeException('audit_chain 尚未初始化，請先執行遷移。');
        }

        return $chain->last_hash === null ? null : (string) $chain->last_hash;
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function digest(
        ?string $prevHash,
        string $event,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        int|string|null $actorId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $ip = null,
        ?string $userAgent = null,
        ?DateTimeInterface $occurredAt = null,
    ): string {
        $payload = json_encode([
            'event' => $event,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId === null ? null : (string) $subjectId,
            'actor_id' => $actorId === null ? null : (string) $actorId,
            'before' => $before,
            'after' => $after,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'occurred_at' => $occurredAt === null
                ? null
                : CarbonImmutable::instance($occurredAt)->utc()->format('Y-m-d\TH:i:s\Z'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

        return hash((string) config('carbon.audit.hash_algo', 'sha256'), ($prevHash ?? '').$payload);
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
