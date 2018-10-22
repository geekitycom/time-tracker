<?php

use App\Entry;
use App\Project;
use App\Task;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProjectTaskEntryStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (Entry::all() as $entry) {
            $titles = explode(': ', $entry->title, 2);
            if (1 === count($titles)) {
                $projectTitle = null;
                $project = null;
            } elseif (2 === count($titles)) {
                $projectTitle = trim($titles[0]);
                try {
                    $project = Project::where('user_id', $entry->user_id)
                        ->where('title', $projectTitle)
                        ->firstOrFail();
                } catch (Exception $e) {
                    $project = new Project();
                    $project->user_id = $entry->user_id;
                    $project->title = $projectTitle;
                    $project->save();
                }
            }

            $taskTitle = trim($entry->title);

            try {
                $task = Task::where('user_id', $entry->user_id)
                    ->where('project_id', is_null($project) ? null : $project->id)
                    ->where('title', $taskTitle)
                    ->firstOrFail();
            } catch (Exception $e) {
                $task = new Task();
                $task->user_id = $entry->user_id;
                $task->project_id = is_null($project) ? null : $project->id;
                $task->title = $taskTitle;
            }

            $task->save();

            $entry->task_id = $task->id;
            $entry->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('entries')->update(['task_id' => null]);
        DB::table('tasks')->truncate();
        DB::table('projects')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
