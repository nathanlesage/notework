# Changelog for Zettlr

## tba

### Fixes

* Sometimes Zettlr crashed when trying to import notes with suggested notes but deleted all notes previously

## v.0.3.0-beta (2016-05-09)

### Features

* **Major feature**: Implemented an importer: Now you can type in whatever program you want and only afterwards import your notes in one step and easily migrate them into your Zettlr. The app will automatically detect notes by their headings and let you confirm potential errors or typos before finally migrating them, even suggesting tags, if you want so. Also you can automatically create an outliner that will then contain these notes.
* You have now additional options when updating outlines, giving you more flexibility to batch-update notes (for example add new references or tags or even synchronize them across your notes)
* Added import feature for `BibTex`-files
* Switch implemented to toggle between sorting notes on outlines and editing them right where they are.
* Now the note linking functionality does not only rely on tags anymore. If you choose not to tag your notes, Zettlr now does a text analysis of your note's contents and displays this instead of a tag-based relevancy index.

### Layout

* Overhauled navbar concept to resemble traditional computer programs and to have the search bar fill in the whole remains of it
* Fixed "sticky" input fields on mobile devices on the "Create notes"-page

### Fixes

* Custom fields on outlines now get automatically deleted when the corresponding outline gets deleted
* Now the AppController isn't accessible without being logged in (i.e. it redirects to /login)
* As of my own misunderstanding of the `.gitignore`-concept several necessary directories were not pushed to the repository, causing fresh installations to not run (specifically: the storage-directories that are not created automatically on install).
* Now on deletion of a reference you will be redirected to the index page
* References are now ordered by author's last name
* Fixed link of reference importer to reference overview
* Moved laravelcollective/html from require-dev to require to prevent install from crashing when installing with flag --no-dev

### Technoloy

* Removed HomeController, everything concerning the app or its users is now handled via the AppController
* Added the CommonMark library to dependencies for import
* Added first documentation (DocBlocks)

## v0.2.0-beta (2016-04-26)

### Features

* **Major feature**: Implemented trails: Zettlr can now search through all linked notes and display individual paths, making it easy to discover new trains of thought or similar thoughts in different texts
* Complete tag overhaul: You can now list them, sort them (by ID, name and frequency) and delete them
* Implemented a tag overview page that displays all notes that have the tag assigned
* Tag buttons now link to their respective overview page
* You can now unlink notes once you have linked them
* Now you can abort the insertion of new content to outlines without having to reload the page

### Layout

* Primary color is now a light green
* Added a logo
* Fixed design of the inputs for adding new content to outlines
* Added margin to tag-buttons
* Adapted the display of tags on the outline editor to match the note editor
* Changed layout of the note's IDs on the overview
* Adapted layout of related notes sidebar
* Changed CodeMirror behaviour: From now on the note editor will resize the more you write

### Fixes

* If an error occurs on updating your user settings, your changed values will be taken over
* Zettlr now takes over tags and references when an error occured on editing or creating new notes
* Fixed an issue, which caused references to be lost after editing, as the reference ID has not been passed to the form
* Fixed a bug that caused outlines to not completely rebuild on page reload when adding custom fields to the outline

### Technology

* Removed vendor directory from GitHub repository
* Adapted the composer.json file to reflect Zettlr is just an app based on laravel and not laravel itself
* Limited retrieved tags, references and notes in searches to 20 (notes: 15) to ease the flood out a little bit
* Switched to a local LESS compiler for more consistency and options to configure bootstrap
* Moved main.css-contents to zettlr_classes.less
* Added an APP_URL environment variable to have the LESS compiler compute the correct paths

## v0.1.1-beta (2016-04-17)

* Fixed accidental submission of forms by hitting `Enter` in a Text field
* Fixed a bug that caused outlines to randomly select notes to show, if there are no custom fields available
* Fixed a bug that caused attached tags from outlines not to be attached to the respective notes and created a `$tag->name` pseudo-tag
* Same issue fixed with references
* Fixed an issue that redirected to the "normal" note creation instead of passing on the outline ID when an error was detected, causing tags and references to be lost
* Fixed `Enter` key submission on login form
* Changed layout for the tagList to make room for more tags and prevent exceeding the bottom of the container for a little bit longer
* Fixed an error where UTF-8 strings (with chars like ä, ö, ø, etc) in notes caused an internal laravel error when using the search field, if these characters were split in half by php's `subtr()`-function

## v0.1.0-beta

* Initial release
