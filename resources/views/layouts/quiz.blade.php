@extends('layouts.root')

@section('content')
<div class="quiz-info">
    @if (!$solution)
    <h2>Test: <span>{{ $name }}</span></h2>
    @else
    <h2>Odpowiedzi: <span>{{ $name }}</span></h2>
    @endif
    <p>Liczba pytań: {{ $questionAmount }}</p>
    @if ($solution)
    <p class="result">Uzyskany wynik: <span>{{ $score }}</span></p>
        @if ($passed)
        <p class="passed">Gratulacje! Udało ci się zdać egzamin :)</p>
        @else
        <p class="failed">Niestety nie udało ci się zdać egzaminu :(</p>
        @endif
    @endif
</div>
@if (!$solution)
<form action="/quizzes/{{ $quizId }}" method="POST">
@endif
    @foreach ($questions as $number => $question)
    <div class="question">
        <p>{{ $number + 1 }}. {{ $question->question }}</p>
        @if ($question->image !== null)
        <img src="{{ $question->image }}">
        @endif
        <div class="answers">
            @if ($solution && $question->wrong === -1)
            <p class="no-answer">Nie udzielono odpowiedzi!</p>
            @endif
            @foreach (['a', 'b', 'c', 'd'] as $i => $l)
            @php
            $class = '';
            if ($solution && $l == $question->right)
                $class = 'right';
            elseif ($solution && $l == $question->wrong)
                $class = 'wrong';
            @endphp
            <label class="{{ $class }}">
                @if (!$solution)
                <input type="radio" name="q_{{ $number + 1}}" value="{{ $l }}" onchange="selectAnswer(this)">
                @endif
                {{ strtoupper($l) }}. {{ $question->answers[$i] }}
            </label>
            @endforeach
        </div>
    </div>
    @endforeach
    <div class="end-quiz">
        <input type="submit" class="end-quiz ui massive primary button" value="Zakończ test">
    </div>
@if (!$solution)
{{ csrf_field() }}
</form>
@endif
@endsection
