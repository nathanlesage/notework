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

        <form method="POST" action="{{ url('/outlines/create') }}" id="createNewOutlineForm" class="form-horizontal">
            {!! csrf_field() !!}

            <div class="form-group{{ $errors->has('name') ? ' has-error has-feedback' : '' }}">
                <div class="col-md-2">
                    <label for="titleInput">Title</label>
                </div>
                <div class="col-md-10">
                    <input type="text" class="form-control" name="name" autofocus="autofocus" placeholder="Title" value="{{ old('name') }}" tabindex="1">
                </div>
            </div>

            <div class="form-group">
                <div class="col-md-2">
                    <label for="tagSearchBox">Associate tags:</label>
                </div>
                <div class="col-md-10">
                    <input class="form-control" style="" type="text" id="tagSearchBox" placeholder="Search for tags &hellip;" tabindex="2">
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-2">
                </div>
                <div class="col-md-10" id="tagList">
                    <!-- Here the tags are appended -->
                    {{-- Old tags from $request --}}
                    @if(count(old('tags')) > 0)
                        @foreach(old('tags') as $tag)
                            {{-- The old object only contains the array --}}
                            <div class="btn btn-primary tag" onClick="$(this).fadeOut(function() { $(this).remove(); })">
                                <input type="hidden" value="{{ $tag }}" name="tags[]">
                                {{ $tag }}
                                <button type="button" class="close" title="Remove" onClick="$(this).parent().fadeOut(function() { $(this).remove(); })">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="form-group">
                <div class="col-md-2">
                    <label for="referenceSearchBox">Associate references:</label>
                </div>
                <div class="col-md-10">
                    <input class="form-control" style="" type="text" id="referenceSearchBox" placeholder="Search for references &hellip;" tabindex="3">
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-2">
                </div>
                <div class="col-md-10" id="referenceList">
                    <!-- Here the references are appended -->
                    @if(count(old('references')) > 0)
                        @foreach(old('references') as $referenceID)
                            <?php $reference = App\Reference::find($referenceID) ?>
                            {{-- The old object only contains the array --}}
                            <div class="alert alert-success alert-dismissable">
                                <input type="hidden" value="{{ $reference->id }}" name="references[]">
                                {{ $reference->author_last }} {{ $reference->year }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="form-group{{ $errors->has('description') ? ' has-error has-feedback' : '' }}">
                <div class="col-md-2">
                    <label for="desc">Description</label>
                </div>
                <div class="col-md-10">
                    <textarea class="form-control" id="desc" name="description" placeholder="Short description (optional)" tabindex="4">{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-2"></div>
                <div class="col-md-10">
                    <button type="submit" class="form-control" tabindex="5">Create Outline</button>
                </div>
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
