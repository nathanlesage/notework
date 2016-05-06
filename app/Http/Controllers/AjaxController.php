<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Note;
use App\Tag;
use App\CustomField;
use App\Outline;
use App\Reference;

use Storage;
use Illuminate\Support\Facades\File;

// For rendering the markup within the view
use GrahamCampbell\Markdown\Facades\Markdown;

/*
* This controller is responsible
* for handling all AJAX-Requests by the application.
*/

class AjaxController extends Controller
{
    /**
    * Create a new controller instance.
    * Use "auth" middleware.
    *
    * @return void
    */
    public function __construct()
    {
        // Require the user to be logged in
        // for every action this controller does
        $this->middleware('auth');
    }

    // Fetch note contents via given ID
    public function getNoteContents($id)
    {
        $note = Note::find($id);
        $note->tags;

        if(! $note)
        {
            // Didn't find the note? Tell the app
            return response()->json(['message', 'Not a valid note'], 404);
        }
        else
        {
            // Otherwise just return our complete note in JSON format
            // Don't forget to parse the content to HTML
            $note->content = Markdown::convertToHtml($note->content);

            return $note;
        }
    }

    // Delete note
    public function getDeleteNote($id)
    {
        $note = Note::find($id);

        if(! $note)
        {
            // Didn't find the note? Tell the app
            return response()->json(['message', 'Not a valid note'], 404);
        }
        else
        {
            // Otherwise delete the note
            $note->forceDelete();
            return response()->json(['message', 'Note deleted successfully'], 200);
        }
    }

    public function getTagSearch($term)
    {
        // The "LIKE"-Statement in SQL just searches for the pattern
        // anywhere in, at the beginning or the end of a term.
        $tags = Tag::where('name', 'LIKE', '%'.$term.'%')->limit(20)->get(['name', 'id']);

        if(! $tags)
        {
            return response()->json(['message', 'No tags match your search term'], 404);
        }
        else
        {
            return $tags;
        }
    }

    public function getReferenceSearch($term)
    {
        // The "LIKE"-Statement in SQL just searches for the pattern
        // anywhere in, at the beginning or the end of a term.
        $references = Reference::where('title', 'LIKE', '%'.$term.'%')
        ->orWhere('author_first', 'LIKE', '%'.$term.'%')
        ->orWhere('author_last', 'LIKE', '%'.$term.'%')
        ->orWhere('title', 'LIKE', '%'.$term.'%')
        ->limit(20)
        ->get();

        if(! $references)
        {
            return response()->json(['message', 'No references match your search term'], 404);
        }
        else
        {
            return $references;
        }
    }

    public function getNoteSearch($term)
    {
        // TODO: implement a "good" fulltext search.

        $notes = Note::where('content', 'LIKE', '%'.$term.'%')
        ->orWhere('title', 'LIKE', '%'.$term.'%')
        ->limit(15)
        ->get(['content', 'title', 'id']);

        // Do we have a search result?
        if(! $notes)
        {
            return response()->json(['message', 'No search results'], 404);
        }
        else
        {
            foreach($notes as $note)
            {
                $note->content = strip_tags(Markdown::convertToHtml(str_limit($note->content, 120, '&hellip;')));
            }

            return $notes;
        }
    }

    public function getLinkNotes($id1, $id2)
    {
        if(($id1 <= 0) || ($id2 <= 0))
        return response()->json(['message', 'Bad request: An ID was invalid'], 400);

        try
        {
            Note::findOrFail($id1);
            Note::findOrFail($id2);
        }
        catch(ModelNotFoundException $e)
        {
            return response()->json(['message', 'Some ID did not belong to any note'], 404);
        }

        // This note is the currently active note
        $note = Note::find($id1);
        $note2 = Note::find($id2);

        //  And it should be attached to this ID (and vice versa)
        $note->notes()->attach($id2);
        $note2->notes()->attach($id1);

        return response()->json(['message', 'Notes linked successfully'], 200);
    }

    public function getUnlinkNotes($id1, $id2)
    {
        if(($id1 <= 0) || ($id2 <= 0))
        return response()->json(['message', 'Bad request: An ID was invalid'], 400);

        try
        {
            Note::findOrFail($id1);
            Note::findOrFail($id2);
        }
        catch(ModelNotFoundException $e)
        {
            return response()->json(['message', 'Some ID did not belong to any note'], 404);
        }

        // This note is the currently active note
        $note = Note::find($id1);
        $note2 = Note::find($id2);

        //  And it should be detached to this ID (and vice versa)
        $note->notes()->detach($id2);
        $note2->notes()->detach($id1);

        return response()->json(['message', 'Notes linked successfully'], 200);
    }

    /*
    * OUTLINER FUNCTIONS
    */
    public function getOutlineAttach($outlineID, $attachmentType, $requestContent, $index, $type = "p")
    {
        // First catch the outline
        try {
            $outline = Outline::findOrFail($outlineID);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message', 'Could not find outline'], 404);
        }

        if($attachmentType == "custom")
        {
            $field = new CustomField;

            $field->type = $type; // type is the HTML tag (i.e. p, h3, div)
            $field->content = nl2br($requestContent); // What goes inside the tag
            $field->index = $index; // The index inside the outliner

            $outline->customFields()->save($field);
            return $field;

        }
        else {
            // attachmentType = note
            // $type is now omittable
            // $requestContent now contains our ID
            try {
                $note = Note::findOrFail($requestContent);
            } catch (ModelNotFoundException $e) {
                return response()->json(['message', 'Could not find note'], 404);
            }
            $outline->notes()->attach($note, ['index' => $index]);
            return $note;

        }
    }

    public function getChangeIndex($type, $elementId, $outlineId, $newIndex)
    {
        if(!$elementId || !$newIndex)
        return response()->json(['message', 'Either no element or no new index was given'], 404);

        if($type == 'custom')
        {
            try {
                $field = CustomField::findOrFail($elementId);
            } catch (ModelNotFoundException $e) {
                return response()->json(['message' => 'No such custom field'], 404);
            }
            $field->index = $newIndex;
            $field->save();
        }
        else {
            // We have a note
            $outline = Outline::find($outlineId)->notes()->updateExistingPivot($elementId, ['index' => $newIndex]);
        }
        return response()->json(['message', 'Updating successful'], 200);
    }

    public function getOutlineDetachNote($outlineId, $noteId)
    {
        if(!$outlineId || !$noteId)
        return response()->json(['message', 'Some ID was empty'], 404);

        $outline = Outline::find($outlineId);
        $outline->notes()->detach($noteId);
        return response()->json(['message', 'Removed note from outliner']);
    }

    public function getOutlineRemoveCustom($outlineId, $customId)
    {
        if(!$outlineId || !$customId)
        return response()->json(['message', 'Some ID was empty'], 404);

        try {
            $field = CustomField::findOrFail($customId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message', 'No such custom field'], 404);
        }

        $field->delete();

        return response()->json(['message', 'Removed custom field from outliner'], 200);
    }

    // This function collects the dropzone.js-uploaded files for later processing
    public function collect(Request $request)
    {
        if(!$request->hasFile('import_tmp'))
        return response()->json(['The file hasn\'t been uploaded!'], 400);

        if(!$request->file('import_tmp')->isValid())
        return response()->json(['The file has been corrupted on upload.'], 500);

        // First initialize our local storage
        $store = Storage::disk('local');

        // Check if folder is present and if not, create it
        $found = false;
        foreach($store->directories('app') as $dir)
        {
            if($dir === 'import')
            $found = true;
        }

        if(!$found)
        $store->makeDirectory('import');

        $fcontents = File::get($request->file('import_tmp')->getRealPath());

        // We're using time() to have a unique identifier for retaining the notes' upload order
        $success = $store->put(
        'import/tmp_'.time().'.'.$request->file('import_tmp')->getClientOriginalExtension(),
        $fcontents);

        if($success)
        return response()->json(['Upload successful'], 200);
        else {
            return response()->json(['Failed to move file to directory'], 500);
        }
    }
}
