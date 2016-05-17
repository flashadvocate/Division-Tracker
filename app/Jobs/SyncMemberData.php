<?php

namespace App\Jobs;

use App\AOD\MemberSync\SyncMemberData as MemberSync;
use App\Slack\Response\Delayed;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SyncMemberData extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * @var
     */
    private $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        MemberSync::execute();

        Delayed::handle('Member sync completed successfully', $this->data);
    }
}