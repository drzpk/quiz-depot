@extends('layouts.root')

@section('content')
<div class="ui items">
    <div class="item category-info">
        <div class="ui medium image">
            @if ($category->image !== null)
            <img src="{{ $category->image }}">
            @else
            <img src="/img/no-image.png">
            @endif
        </div>
        <div class="content">
            <p class="header">{{ $category->name }}</p>
            <div class="description">
                <p>{{ $category->description }}</p>
            </div>
        </div>
    </div>
</div>
<h2 class="ui header">Lista testów</h2>
<div class="ui list">
@foreach($quizzes as $quiz)
<div class="item">
    <a class="header" href="/quizzes/{{ $quiz->id }}">{{ $quiz->name }}</a>
    Dostępnych pytań: {{ $quiz->questionCount }}
    <br>
    Losowanych pytań: {{ $quiz->questionChunkSize }}
    <br>
    Liczba podejść: {{ $quiz->attempts }}
</div>
@endforeach
</div>
@endsection