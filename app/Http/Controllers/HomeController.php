<?php

namespace App\Http\Controllers;

use App\Entry;
use App\Jobs\CalculateEntryDuration;
use Carbon\Carbon;
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
            ->get();

        $index = [];

        foreach ($rows as $entry) {
            $title = trim($entry->title);

            if (isset($index[$entry->title])) {
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

        $entry = new Entry();
        $entry->user()->associate(Auth::user());
        $entry->title = $request->title;
        if (empty($request->time)) {
            $entry->started_at = Carbon::now();
        } else {
            $entry->started_at = Carbon::createFromTimeString($request->time);
        }

        $entry->started_at = $entry->started_at->setDate(
            $start->year, $start->month, $start->day
        );

        $entry->started_at->second = 0;

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
