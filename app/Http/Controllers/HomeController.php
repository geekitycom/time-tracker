<?php

namespace App\Http\Controllers;

use App\Entry;
use App\Project;
use App\Task;
use App\Jobs\CalculateEntryDuration;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $today = Carbon::today();

        if (empty($request->start)) {
            $start = Carbon::today();
        } else {
            $start = new Carbon($request->start . ' 00:00:00');
        }

        $prev = $start->copy()->subDay();

        if (empty($request->end)) {
            $end = $start->copy()->addDay();
        } else {
            $end = new Carbon($request->end . ' 00:00:00');
        }

        $rows = Entry::where([
                ['started_at', '>=', $start],
                ['started_at', '<', $end],
            ])
            ->orderBy('started_at', 'desc')
            ->with('task')
            ->get();

        $index = [];

        foreach ($rows as $entry) {
            $title = trim($entry->task->title);

            if (isset($index[$entry->task->title])) {
                $index[$title]->duration += $entry->duration;
                $index[$title]->children[] = $entry;
            } else {
                $index[$title] = clone $entry;
                $index[$title]->children = [$entry];
            }
        }

        $entries = collect(array_values($index));

        // dd([$prev, $start, $end, $today]);

        return view('home', compact('entries', 'prev', 'start', 'end', 'today'));
    }

    public function store(Request $request)
    {
        if (empty($request->start)) {
            $start = Carbon::today();
        } else {
            $start = new Carbon($request->start . ' 00:00:00');
        }

        $stop = (isset($request->action) && 0 == strcmp('stop', $request->action));

        $titles = explode(': ', $request->title, 2);
        if (1 === count($titles)) {
            $projectTitle = null;
            $project = null;
        } elseif (2 === count($titles)) {
            $projectTitle = trim($titles[0]);
            try {
                $project = Project::where('user_id', Auth::id())
                    ->where('title', $projectTitle)
                    ->firstOrFail();
            } catch (Exception $e) {
                $project = new Project();
                $project->user_id = Auth::id();
                $project->title = $projectTitle;
                $project->save();
            }
        }

        $taskTitle = trim($request->title);

        if ($stop && empty($taskTitle)) {
            $taskTitle = 'Stop';
        }

        try {
            $task = Task::where('user_id', Auth::id())
                ->where('project_id', is_null($project) ? null : $project->id)
                ->where('title', $taskTitle)
                ->firstOrFail();
        } catch (Exception $e) {
            $task = new Task();
            $task->user_id = Auth::id();
            $task->project_id = is_null($project) ? null : $project->id;
            $task->title = $taskTitle;
        }

        $task->save();

        $entry = new Entry();
        $entry->user()->associate(Auth::user());
        $entry->task()->associate($task);
        if (empty($request->time)) {
            $entry->started_at = Carbon::now();
        } else {
            $entry->started_at = Carbon::createFromTimeString($request->time);
        }

        $entry->started_at = $entry->started_at->setDate(
            $start->year, $start->month, $start->day
        );

        $entry->started_at->second = 0;
        $entry->stop = $stop;

        $entry->save();

        CalculateEntryDuration::dispatch($entry);

        return redirect()->route('home', ['start' => $start->format('Y-m-d')]);
    }

    public function destroy(Request $request)
    {
        if (empty($request->start)) {
            $start = Carbon::today();
        } else {
            $start = new Carbon($request->start . ' 00:00:00');
        }

        $entry = Entry::find($request->id);

        if (empty($entry)) {
            return redirect()->route('home');
        }

        $prev = $entry->prev();
        $next = $entry->next();

        $entry->delete();

        if ($prev instanceof Entry) {
            CalculateEntryDuration::dispatch($prev);
        }

        if ($next instanceof Entry) {
            CalculateEntryDuration::dispatch($next);
        }

        return redirect()->route('home', ['start' => $start->format('Y-m-d')]);
    }
}
