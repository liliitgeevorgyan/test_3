<?php

namespace App\Jobs;

use App\Models\Click;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessClickJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * The click data to process
     *
     * @var array
     */
    protected $clickData;

    /**
     * Create a new job instance.
     *
     * @param array $clickData
     * @return void
     */
    public function __construct(array $clickData)
    {
        $this->clickData = $clickData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Check if click already exists (prevent duplicates)
            $existingClick = Click::where('click_id', $this->clickData['click_id'])->first();
            
            if ($existingClick) {
                Log::info('Click already exists, skipping', ['click_id' => $this->clickData['click_id']]);
                return;
            }

            // Create new click record
            Click::create([
                'click_id' => $this->clickData['click_id'],
                'offer_id' => (int) $this->clickData['offer_id'],
                'source' => $this->clickData['source'],
                'timestamp' => Carbon::parse($this->clickData['timestamp']),
                'signature' => $this->clickData['signature'],
            ]);

            Log::info('Click processed successfully', ['click_id' => $this->clickData['click_id']]);
        } catch (\Exception $e) {
            Log::error('Failed to process click', [
                'error' => $e->getMessage(),
                'click_data' => $this->clickData
            ]);
            
            // Re-throw the exception to trigger job retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Click processing job failed permanently', [
            'error' => $exception->getMessage(),
            'click_data' => $this->clickData
        ]);
    }
}
