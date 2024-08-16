<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
    * Show Task Dashboard
    */
Route::get('/', function () {
    Log::info("Get /");
    $startTime = microtime(true);
    // Simple cache-aside logic
    if (Cache::has('tasks')) {
        $data = Cache::get('tasks');
        return view('tasks', ['tasks' => $data, 'elapsed' => microtime(true) - $startTime]);
    } else {
        $data = Task::orderBy('created_at', 'asc')->get();
        Cache::add('tasks', $data);
        return view('tasks', ['tasks' => $data, 'elapsed' => microtime(true) - $startTime]);
    }
});

/**
    * Add New Task
    */
Route::post('/task', function (Request $request) {
    Log::info("Post /task");
    $validator = Validator::make($request->all(), [
        'name' => 'required|max:255',
        'file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048'
    ]);

    if ($validator->fails()) {
        Log::error("Add task failed.");
        return redirect('/')
            ->withInput()
            ->withErrors($validator);
    }

    $task = new Task;
    $task->name = $request->name;
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $filename = time().'_'.$file->getClientOriginalName();
        $path = Storage::disk('azure')->putFileAs('tasklist', $file, $filename); 
        $task->file_path = $path;
    }
    $task->save();
    // Clear the cache
    Cache::flush();

    return redirect('/');
});

/**
    * Delete Task
    */
Route::delete('/task/{id}', function ($id) {
    Log::info('Delete /task/'.$id);
    Task::findOrFail($id)->delete();
    // Clear the cache
    Cache::flush();

    return redirect('/');
});
