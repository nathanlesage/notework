<?php

namespace App\Http\Controllers;

use Validator;

use App\Note;
use App\Tag;
use App\Outline;
use App\Reference;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

use Illuminate\View\View;

// For rendering the markup within the view
use GrahamCampbell\Markdown\Facades\Markdown;

class NoteController extends Controller
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

    /**
    *  Displays a list of all notes
    *
    *  @return  Illuminate\Http\Response
    */
    public function index() {
        // For index output a list of all notes
        // views/notes/list.blade.php

        $notes = Note::get(['title', 'id']);

        return view('notes.list', ['notes' => $notes]);
    }

    /**
    *  Displays a single note
    *
    *  @param   int  $id  Note id
    *
    *  @return  Response
    */
    public function show($id) {
        // eager load Note with its tags
        $note = Note::find($id);
        $note->tags;

        // does the note exist? If not, return error
        if(!$note) {
            return redirect('notes/index')->withErrors(['That note does not exist!']);
        }

        // Get all note and their tags if the tag ID is in our tags-array
        $tags = [];
        foreach($note->tags as $tag) {
            $tags[] = $tag->id;
        }

        // Get all notes that have at least one of this note's tags
        $relatedNotes = Note::whereHas('tags', function($query) use($tags) {
            $query->whereIn('tag_id', $tags);
        })->get();

        // Now remove this note from collection (to reduce inception level)
        $relatedNotes = $relatedNotes->keyBy('id');
        $relatedNotes->forget($note->id);

        // Now we have all relatedNotes
        // But they have ALL tags with them.
        // We have to determine the relevancy manually
        $maxCount = 0;
        foreach($relatedNotes as $n)
        {
            // count all similar tags and write a count-attribute to the model
            $count = 0;

            foreach($n->tags as $t) {
                foreach($note->tags as $t2) {
                    if($t->id == $t2->id) {
                        $count++;
                        break;
                    }
                }
            }

            $n->count = $count;
            // write current maxCount
            if($count > $maxCount) {
                $maxCount = $count;
            }
        }

        // Now we need to sort the relatedNotes by relevancy
        //$relatedNotes = array_values(array_sort($relatedNotes, function($value) { return $value->count; }));
        //array_multisort($relatedNotes, "SORT_DESC", );
        $relatedNotes = Collection::make($relatedNotes)->sortBy(function($value) { return $value->count; }, SORT_REGULAR, true)->all();

        // Now it can be, that some people are just ignoring the tag functionality
        // which is fine. But we then need another algorithm to search notes:
        // heuristic text analysis!
        if(count($note->tags) == 0 || count($relatedNotes) < 5) {
            $relatedNotes = $this->textAnalysis($note);

            // Now retrieve all related Notes
            $tmp = new Collection();
            $maxCount = 0;
            foreach($relatedNotes as $id)
            {
                $n = Note::find($id['id']);
                $n->count = $id['relevancy'];
                $tmp->push($n);

                if($id['relevancy'] > $maxCount) {
                    $maxCount = $id['relevancy'];
                }
            }
            $relatedNotes = Collection::make($tmp)->sortBy(function($value) { return $value->count; }, SORT_REGULAR, true)->all();

            // Remove this note to reduce inception level
            $thisNoteIndex = -1;
            foreach($relatedNotes as $index => $n)
            {
                if($note->id == $n->id)
                $thisNoteIndex = $index;
            }
            unset($relatedNotes[$thisNoteIndex]);
            $relatedNotes = array_values($relatedNotes);
        }
        // Now retrieve IDs and title of all linked notes
        $linkedNotes = $note->notes;
        $mainID = $note->id;
        return view('notes.show', compact('note', 'relatedNotes', 'maxCount', 'linkedNotes', 'mainID'));
    }

    /**
    *  Displays a form to add a single note
    *
    *  @param   integer  $outlineId  Outline id
    *
    *  @return  Response
    */
    public function getCreate($outlineId = 0) {
        if($outlineId > 0)
        {
            try {
                $outline = Outline::findOrFail($outlineId);
                // Eager load tags
                $outline->tags;
                // Display form to create a new note
                return view('notes.create', ['outline' => $outline]);
            } catch (ModelNotFoundException $e) {
                // Okay, obviously we don't have an outline. Just return the view.
                return view('notes.create', ['outline' => null]);
            }
        }
        // No outline? Just return the view.
        return view('notes.create', ['outline' => null]);
    }

    /**
    *  Inserts a post into the database
    *
    *  @param   Request  $request
    *
    *  @return  Response
    */
    public function postCreate(Request $request) {
        // Insert a note into the db
        // New tags have ID = -1!

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'content' => 'required|min:50',
        ]);

        if ($validator->fails()) {
            if($request->outlineId > 0) {
                return redirect('/notes/create/'.$request->outlineId)
                ->withErrors($validator)
                ->withInput();
            }
            else {
                return redirect('/notes/create')
                ->withErrors($validator)
                ->withInput();
            }
        }

        $note = new Note;
        $note->title = $request->title;
        $note->content = $request->content;

        $note->save();

        // Now fill the join table
        if(count($request->tags) > 0)
        {
            foreach($request->tags as $tagname)
            {
                $tag = Tag::firstOrCreate(["name" => $tagname]);
                $note->tags()->attach($tag->id);
            }
        }

        if(count($request->references) > 0)
        {
            foreach($request->references as $referenceId)
            {
                try {
                    Reference::findOrFail($referenceId);
                    // If this line is executed the model exists
                    $note->references()->attach($referenceId);

                } catch (ModelNotFoundException $e) {
                    // Do nothing
                }
            }
        }

        if($request->outlineId > 0)
        {
            $outline = Outline::find($request->outlineId);
            // Which index should we use? Of course the last.
            $noteIndex = count($outline->customFields) + count($outline->notes) + 1;
            // Attach to this outline
            $outline = Outline::find($request->outlineId)->notes()->attach($note, ['index' => $noteIndex]);
            return redirect(url('/notes/create/'.$request->outlineId));
        }

        // Now redirect to note create as the user
        // definitely wants to add another note.
        return redirect(url('/notes/create'));
    }

    /**
    *  Displays a form with prefilled values
    *
    *  @param   integer  $id  Note id
    *
    *  @return  Response
    */
    public function getEdit($id) {
        $note = Note::find($id);
        $note->tags;
        $note->references;
        return view('notes.edit', ['note' => $note]);
    }

    /**
    *  Updates a note
    *
    *  @param   Request  $request
    *  @param   integer   $id       note id
    *
    *  @return  Response
    */
    public function postEdit(Request $request, $id) {
        // Update a note

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'content' => 'required|min:3',
        ]);

        if ($validator->fails()) {
            return redirect('/notes/edit/'.$id)
            ->withErrors($validator)
            ->withInput();
        }

        // Get the note
        $note = Note::find($id);

        // First add any potential new tags to the database.
        // And also attach them if not done yet
        if(count($request->tags) > 0)
        {
            $tagIDs = [];

            foreach($request->tags as $tagname)
            {
                $tag = Tag::firstOrCreate(["name" => $tagname]);
                $tagIDs[] = $tag->id;
            }
            // Sync tag list
            $note->tags()->sync($tagIDs);
        }
        else {
            // Sync with empty array to remove all
            $note->tags()->sync([]);
        }

        if(count($request->references) > 0)
        {
            // Same for references
            $referenceIDs = [];

            foreach($request->references as $referenceId)
            {
                try {
                    $ref = Reference::findOrFail($referenceId);
                    // If this line is executed the model exists
                    $referenceIDs[] = $ref->id;

                } catch (ModelNotFoundException $e) {
                    // Do nothing
                }
            }

            $note->references()->sync($referenceIDs);
        }
        else {
            // Sync with empty array to remove all
            $note->references()->sync([]);
        }

        // Update the remaining fields
        $note->title = $request->title;
        $note->content = $request->content;
        $note->save();

        // Now redirect to note create as the user
        // definitely wants to add another note.
        return redirect(url('/notes/show/'.$id));
    }

    /**
    *  Removes a note from database
    *
    *  @param   integer  $id  Note id
    *
    *  @return  Response
    */
    public function delete($id) {
        try
        {
            $note = Note::findOrFail($id);
        }
        catch(ModelNotFoundException $e)
        {
            // Didn't find the note? Tell the user.
            return redirect('/notes/index')
            ->withErrors(['No note with specified ID found.']);
        }

        $note->delete();

        return redirect(url('/notes/index'));
    }

    /**
    *  Runs a comparision between notes on text basis
    *
    *  @param   Note     $note  The note, for which related notes should be returned
    *
    *  @return  array         An array containing the IDs and relevancy of related notes
    */
    function textAnalysis(Note $note)
    {
        // For a text analysis we need to reduce this note and
        // reduce all possible related notes to a small index.
        // TODO: Implement an indexing function.

        // explode note contents and title by words
        $title = explode(" ", $note->title);
        $tmp = strip_tags(preg_replace('/[^a-zA-Z0-9[:space:]\p{L}]/u', '', $note->content));
        $tmp = str_replace(["\n", "\r"], " ", $tmp);
        $content = explode(" ", $tmp);
        // Remove duplicates and reset array keys to Zero-based
        $content = array_values(array_unique($content));
        $suggestedNotes = [];
        $rel = [];
        // Now suggest tags for all words with > 3 letters
        for($i = 0; $i < count($content); $i++)
        {
            if(strlen($content[$i]) > 3)
            {
                // I guess above 300+ notes this will be one of the slowest
                // functions in this whole app. Let's see if it breaks the
                // 60sec wall of the server ...
                $notes = Note::where('content', 'LIKE', '%'.$content[$i].'%')->get();
                if(count($notes) > 0)
                {
                    foreach($notes as $index => $n)
                    {
                        // Compare the arrays to determine the relevancy
                        $suggestedNotes[$index] = $n->id;

                        $tmp = strip_tags(preg_replace('/[^a-zA-Z0-9[:space:]\p{L}]/u', '', $n->content));
                        $tmp = str_replace(["\n", "\r"], " ", $tmp);
                        $suggestContent = explode(" ", $tmp);
                        // Remove duplicates and reset array keys to Zero-based
                        $suggestContent = array_values(array_unique($suggestContent));

                        $relevancy = count(array_intersect($content, $suggestContent));
                        $rel[$index] = $relevancy;
                    }
                }
            }
        }

        // Remove potential duplicate notes
        $suggestedNotes = array_values(array_unique($suggestedNotes));
        // now write the index of relevancy into the remaining $suggestedNotes
        unset($tmp);
        $tmp = [];
        foreach($suggestedNotes as $index => $id)
        {
            $tmp[$index]['id'] = $id;
            $tmp[$index]['relevancy'] = $rel[$index];
        }
        $suggestedNotes = $tmp;

        return $suggestedNotes;
    }
}
