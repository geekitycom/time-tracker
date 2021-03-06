@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row pt-2 pb-2">
        <div class="col-sm">
            <a class="btn btn-secondary btn-sm" href="{{ route('home', ['start' => $prev->format('Y-m-d') ]) }}" role="button">{{ $prev->format('M j, Y') }}</a>
        </div>
        <div class="col-sm text-center {{ $start->notEqualTo($today) ? 'text-danger' : '' }}">
            <h2>{{ $start->format('M j, Y') }}</h2>
        </div>
        <div class="col-sm text-right">
            @if ($start->lessThan($today) && $end->notEqualTo($today))
            <a class="btn btn-secondary btn-sm" href="{{ route('home', ['start' => $end->format('Y-m-d') ]) }}" role="button">{{ $end->format('M j, Y') }}</a>
            @endif
            @if ($start->notEqualTo($today))
            <a class="btn btn-secondary btn-sm" href="{{ route('home') }}" role="button">Today</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">

<form method="post" action="{{ route('home.store') }}" autocomplete="off">
    @csrf
    <input type="hidden" name="start" value="{{ $start->format('Y-m-d') }}">
    <div class="form-row">
        <div class="form-group col-md-2 col-lg-1">
            <label for="time" class="sr-only">Time</label>
            <input type="text" class="form-control text-lg-right" name="time" id="time" placeholder="00:00">
        </div>
        <div class="form-group col-md-8 col-lg-10">
            <label for="title" class="sr-only">Title</label>
            <input type="text" class="form-control" name="title" id="title" placeholder="Title">
        </div>
        <div class="form-group col-md-2 col-lg-1">
            <div class="btn-group" role="group" aria-label="Basic example">
                <button type="submit" name="action" value="start" class="btn btn-outline-primary"><font-awesome-icon :icon="['fas', 'play']"></font-awesome-icon></button>
                <button type="submit" name="action" value="stop" class="btn btn-outline-danger"><font-awesome-icon :icon="['fas', 'stop']"></font-awesome-icon></button>
            </div>
        </div>
    </div>
</form>

@foreach ($entries as $entry)
    <div class="row pt-2 pb-2">
        <div class="col-md-10 col-lg-11">
            <button
                class="btn btn-link entry-toggle-children entry-collapsed"
                data-children="entry-children-{{ $entry->id }}"
            >
                <span class="icon-collapsed"><font-awesome-icon :icon="['fas', 'caret-right']"></font-awesome-icon></span>
                <span class="icon-expanded"><font-awesome-icon :icon="['fas', 'caret-down']"></font-awesome-icon></span>
            </button>
            <div class="entry-time-duration">{{ $entry->timeDuration() }}</div>
            <div class="entry-title">{{ $entry->task->title }}</div>
        </div>
        <div class="col-md-2 col-lg-1 text-lg-right">
            <form method="post" action="{{ route('home.store') }}">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $entry->id }}">
                <input type="hidden" name="title" value="{{ $entry->task->title }}">
                <input type="hidden" name="start" value="{{ $start->format('Y-m-d') }}">
                <button type="submit" class="btn btn-link btn-sm"><font-awesome-icon :icon="['fas', 'play']"></font-awesome-icon></button>
            </form>
        </div>
    </div>
    <div class="pt-2 pb-2" id="entry-children-{{ $entry->id }}" style="display: none">
    @foreach ($entry->children as $child)
        <div class="row mb-1">
            <div class="col-md-10 col-lg-10">
                <div class="entry-time-padding">&nbsp;</div>
                <div class="entry-time-duration text-muted">{{ $child->timeDuration() }}</div>
                <div class="entry-title text-muted">{{ $child->started_at->format('M j, g:i a') }}</div>
            </div>
            <div class="col-md-2 col-lg-2 text-lg-right">
                <form method="post" action="{{ route('home.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="id" value="{{ $child->id }}">
                    <input type="hidden" name="start" value="{{ $start->format('Y-m-d') }}">
                    <button type="submit" class="btn btn-link btn-sm"><span class="text-muted">Delete</span></button>
                </form>
            </div>
        </div>
    @endforeach
    </div>
@endforeach


        </div>
    </div>
</div>
@endsection

@push('footer')

    <script>
        $( document ).ready(function() {
            $('.entry-toggle-children').click(function () {
                var toggle = $(this);
                var childrenId = toggle.data('children');
                var children = $('#' + childrenId);
                children.toggle();
                if (children.is(':visible')) {
                    toggle.removeClass('entry-collapsed');
                    toggle.addClass('entry-expanded');
                } else {
                    toggle.removeClass('entry-expanded');
                    toggle.addClass('entry-collapsed');
                }
                return false;
            });
        });
    </script>
@endpush
