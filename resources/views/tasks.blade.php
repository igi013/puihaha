<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task List</title>
    <style>
        :root {
            color-scheme: light;
            --canvas: #fff;
            --paper: #fff;
            --ink: #151619;
            --muted: #62656c;
            --line: #d9dade;
            --accent: #c52430;
            --accent-dark: #a41b26;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--canvas);
            color: var(--ink);
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 16px;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }
        .masthead { background: #101114; color: #fff; }
        .masthead-inner { max-width: 1320px; margin: auto; padding: 28px 48px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
        .wordmark { font-size: 18px; letter-spacing: .24em; font-weight: 650; text-transform: uppercase; }
        .masthead a { color: #e1e1e3; font-size: 14px; text-decoration: none; padding: 9px 0; border-bottom: 1px solid transparent; transition: border-color 150ms; }
        .masthead a:hover { border-color: #fff; }
        .masthead a:focus-visible { outline: 2px solid #fff; outline-offset: 6px; }
        .hero { background: #101114; color: #fff; }
        .hero-inner { max-width: 1320px; padding: 66px 48px 80px; margin: auto; }
        .hero h1 { font-size: clamp(58px, 8.5vw, 112px); font-weight: 500; letter-spacing: -.055em; line-height: 1.05; }
        .hero h1::after { content: '.'; color: #e13a45; }
        .workspace { max-width: 1320px; margin: 0 auto; padding: 64px 48px 96px; }
        .page-header { display: flex; align-items: center; gap: 16px; margin-bottom: 40px; }
        .page-mark {
            width: 44px; height: 44px; display: grid; place-items: center;
            color: var(--accent); background: #e5eee9; border-radius: 12px;
        }
        .page-mark svg { width: 24px; height: 24px; }
        h1 { margin: 0; font-size: clamp(30px, 4vw, 40px); line-height: 1.15; letter-spacing: -.035em; font-weight: 650; }
        .task-layout { display: grid; grid-template-columns: minmax(280px, 390px) minmax(0, 1fr); gap: clamp(40px, 7vw, 112px); align-items: start; }
        .task-form { padding: 0; background: var(--paper); border-radius: 0; }
        .field + .field { margin-top: 24px; }
        label { display: block; margin-bottom: 10px; font-size: 15px; font-weight: 500; }
        input, textarea {
            display: block; width: 100%; border: 1px solid #96989e; border-radius: 4px;
            padding: 14px 15px; background: var(--paper); color: var(--ink);
            font: inherit; line-height: 1.5; transition: border-color 140ms, box-shadow 140ms;
        }
        textarea { min-height: 152px; resize: vertical; }
        input:hover, textarea:hover { border-color: #929da8; }
        input:focus, textarea:focus { outline: 2px solid var(--ink); outline-offset: 3px; border-color: var(--ink); }
        input[aria-invalid="true"] { border-color: #b53c38; }
        .field-error { margin: 8px 0 0; color: #a52e2e; font-size: 14px; }
        button {
            display: flex; justify-content: center; align-items: center; gap: 9px;
            width: 100%; margin-top: 28px; padding: 12px 18px; min-height: 46px;
            border: 0; border-radius: 4px; background: var(--ink); color: #fff;
            font: inherit; font-size: 15px; font-weight: 500; cursor: pointer;
            transition: background 140ms, transform 100ms;
        }
        button svg { width: 17px; height: 17px; }
        button:hover { background: var(--accent); }
        button:active { transform: scale(.985); }
        button:focus-visible { outline: 3px solid var(--accent); outline-offset: 4px; }
        .list-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 0 0 20px; border-bottom: 1px solid var(--line); }
        h2 { margin: 0; font-size: 28px; line-height: 1.3; letter-spacing: -.025em; font-weight: 500; }
        .task-count { font-size: 15px; color: var(--muted); font-variant-numeric: tabular-nums; }
        .empty-state { min-height: 310px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 18px; padding: 40px 20px; text-align: center; }
        .empty-state svg { width: 56px; height: 56px; color: #8a8d94; }
        .empty-state p { margin: 0; color: var(--muted); font-size: 14px; }
        .task-item { display: flex; gap: 15px; padding: 24px 0; border-bottom: 1px solid var(--line); }
        .task-indicator { flex: 0 0 18px; width: 18px; height: 18px; margin-top: 5px; border: 1.5px solid #9c9ea5; border-radius: 50%; }
        .task-indicator.completed { border-color: var(--ink); background: var(--ink); }
        .task-content { min-width: 0; }
        h3 { margin: 0; font-size: 16px; line-height: 1.5; font-weight: 600; overflow-wrap: anywhere; }
        .task-description { margin: 7px 0 12px; color: var(--muted); font-size: 14px; white-space: pre-wrap; overflow-wrap: anywhere; }
        .task-status { margin: 0; font-size: 12px; color: var(--muted); }
        .success-message { padding: 14px 18px; margin: 0 0 32px; color: #244c36; background: #edf5ef; border-radius: 4px; font-size: 14px; }
        ::selection { background: #c52430; color: #fff; }
        @media (max-width: 800px) {
            .workspace { padding: 36px 24px 64px; }
            .masthead-inner { padding: 22px 24px; }
            .wordmark { font-size: 14px; letter-spacing: .18em; }
            .hero-inner { padding: 44px 24px 52px; }
            .task-layout { grid-template-columns: minmax(0, 1fr); gap: 36px; }
            .page-header { margin-bottom: 28px; }
            .task-form { padding: 0; }
            .empty-state { min-height: 220px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition: none !important; }
            button:active { transform: none; }
        }
    </style>
</head>
<body>
    <header class="masthead">
        <div class="masthead-inner">
            <span class="wordmark">Task List</span>
            <a href="#saved-tasks-heading">Saved Tasks <span aria-hidden="true">↗</span></a>
        </div>
    </header>
    <section class="hero" aria-labelledby="page-heading">
        <div class="hero-inner"><h1 id="page-heading">My Tasks</h1></div>
    </section>
    <main class="workspace">

    @if (session('success'))
        <p class="success-message" role="status">{{ session('success') }}</p>
    @endif

    <div class="task-layout">
    <form class="task-form" action="{{ route('tasks.store') }}" method="POST">
        @csrf

        <div class="field">
        <label for="task_title">Task title:</label>
        <input type="text" id="task_title" name="task_title"
               value="{{ old('task_title') }}"
               @error('task_title') aria-invalid="true" aria-describedby="task-title-error" @enderror>
        @error('task_title')
            <p class="field-error" id="task-title-error">{{ $message }}</p>
        @enderror
        </div>

        <div class="field">
        <label for="description">Description:</label>
        <textarea id="description" name="description">{{ old('description') }}</textarea>
        </div>

        <button type="submit"><svg aria-hidden="true" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><path d="M10 4v12M4 10h12"/></svg>Add Task</button>
    </form>

    <section aria-labelledby="saved-tasks-heading">
    <div class="list-header">
        <h2 id="saved-tasks-heading">Saved Tasks</h2>
        <span class="task-count">{{ $tasks->count() }}</span>
    </div>

    @forelse ($tasks as $task)
        <article class="task-item">
            <span class="task-indicator {{ $task->completed ? 'completed' : '' }}" aria-hidden="true"></span>
            <div class="task-content">
            <h3>{{ $task->task_title }}</h3>
            <p class="task-description">{{ $task->description ?: 'No description provided.' }}</p>
            <p class="task-status">Status: {{ $task->completed ? 'Completed' : 'Not completed' }}</p>
            </div>
        </article>
    @empty
        <div class="empty-state">
            <svg aria-hidden="true" viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="13" y="11" width="30" height="37" rx="4"/><rect x="22" y="7" width="12" height="8" rx="2" fill="var(--canvas)"/><path d="m21 29 4 4 10-10M21 40h14"/></svg>
            <p>No tasks have been added yet.</p>
        </div>
    @endforelse
    </section>
    </div>
    </main>
</body>
</html>
