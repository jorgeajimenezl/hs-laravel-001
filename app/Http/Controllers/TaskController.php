<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::where('author_id', auth()->id())->get()->sortBy('created_at')->partition(function ($task) {
            return ! $task->isCompleted();
        });
        $uncompletedTasks = $tasks[0];
        $completedTasks = $tasks[1];

        return view('tasks.index')->with([
            'uncompletedTasks' => $uncompletedTasks,
            'completedTasks' => $completedTasks,
        ]);
    }

    public function create()
    {
        $allTags = Tag::where('user_id', auth()->id())->get();

        return view('tasks.create', compact('allTags'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => ['required', 'string'],
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'tags' => ['array'],
        ]);

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'author_id' => auth()->id(),
        ]);

        $task->tags()->sync($request->tags);

        return redirect()->route('tasks.index');
    }

    public function show(int $id)
    {
        $task = Task::findOrFail($id);
        $allTags = Tag::where('user_id', auth()->id())->get();

        $task->authorized();

        return view('tasks.show', compact('task', 'allTags'));
    }

    public function toggleComplete(Request $request, Task $task)
    {
        $task->update([
            'completed_at' => $request->completed ? now() : null,
        ]);

        return response()->noContent();
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['required', 'string'],
            'tags' => ['array'],
        ]);

        $task = Task::find($id);

        if ($task) {
            $task->update([
                'title' => $request->title,
                'description' => $request->description,
            ]);

            $task->tags()->sync($request->tags);
            session()->flash('success', $task->title);

            return redirect()->route('tasks.show', $task->id);
        } else {
            return redirect()->route('tasks.index')->withErrors('Task not found');
        }
    }

    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return redirect()->route('tasks.index');
    }
}
