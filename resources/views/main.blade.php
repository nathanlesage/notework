{{-- Main template for the Home section --}}
@extends('app')

{{-- ToDo: Either display login form OR display list of entries --}}

@section('content')
    <div class="container">
        
        <!-- Little hack for vertical alignment -->
        <div class="jumbotron" style="margin-top:25%;">
            <h1>noteworks</h1>
            <p>Noteworks is a free and open-source Zettelkasten adoption. <button class="btn btn-primary">Learn more &hellip;</button></p>
            <p>You are logged in as <strong>{{ Auth::user()->name }}</strong>.</p>
        </div>
    </div>
@endsection