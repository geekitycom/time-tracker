<?php

namespace App\Console\Commands;

use App\Entry;
use App\Jobs\CalculateEntryDuration;
use Illuminate\Console\Command;

class RecalculateDuration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'time:recalculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate durations';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $entries = Entry::orderBy('started_at', 'desc')->get();

        foreach ($entries as $entry) {
            $start = $entry->started_at;
            $start->second = 0;
            if ($entry->started_at->ne($start)) {
                $entry->started_at = $start;
                $entry->save();
            }

            CalculateEntryDuration::dispatch($entry);
        }
    }
}
