<?php

namespace App\Jobs;

use App\Services\PurchaseBreakupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateBreakupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    /**
     * @param  array{tenant_id?: int, dbserver?: ?string, dbuser?: ?string, dbpassword?: ?string, dbname?: ?string}  $tenantContext
     */
    public function __construct(
        public int $jobId,
        public int $purchaseId,
        public array $tenantContext
    ) {}

    public function handle(PurchaseBreakupService $service): void
    {
        $service->processJob($this->jobId, $this->purchaseId, $this->tenantContext);
    }
}
