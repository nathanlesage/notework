@extends('app')

@section('scripts')
@endsection

{{--<script> for highlighing--}}
@section('scripts_on_document_ready')

@endsection
{{--</script>--}}

@section('content')
<div class="container" style="background-color:white;">
  <div class="page-header">
    <h1>Create new outline</h1>
  </div>

    <form method="POST" action="{{ url('/outlines/create') }}" id="createNewOutlineForm">
            {!! csrf_field() !!}

            <div class="form-group{{ $errors->has('name') ? ' has-error has-feedback' : '' }}">
            		<label for="titleInput" class="sr-only">Title</label>
                	<input type="text" class="form-control" name="name" autofocus="autofocus" placeholder="Title" value="{{ old('name') }}">
            </div>

            <div class="form-group">
              <input class="form-control" style="" type="text" id="tagSearchBox" placeholder="Search for tags &hellip;">
            </div>
            <div class="form-group">
              <div id="tagList">
                <!-- Here the tags are appended -->
              </div>
            </div>

            <div class="form-group">
              <input class="form-control" style="" type="text" id="referenceSearchBox" placeholder="Search for references &hellip;">
            </div>
            <div class="form-group">
              <div id="referenceList">
                <!-- Here the references are appended -->
              </div>
            </div>

            <div class="form-group{{ $errors->has('description') ? ' has-error has-feedback' : '' }}">
            		<label for="desc" class="sr-only">Description</label>
                	<textarea class="form-control" id="desc" name="description" placeholder="Short description (optional)">{{ old('description') }}</textarea>
            </div>
            <div class="form-group">
            	<button type="submit" class="btn btn-default">Create Outline</button>
            </div>
		</form>
		@if (count($errors) > 0)
		<div class="alert alert-danger">
		<ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        </div>
		@endif
</div>
@endsection
