@extends('layouts.root')

@section('content')
<h2 class="header">Lista kategorii testów</h2>
<div class="ui items category-list">
    @foreach ($categories as $category)
    <div class="item">
        <div class="ui small image">
            @if ($category->image !== null)
            <img src="{{ $category->image }}">
            @else
            <img src="img/no-image.png">
            @endif
        </div>
        <div class="content">
            <a class="header">{{ $category->name }}</a>
            <div class="description">
                <p>{{ $category->description }}</p>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection