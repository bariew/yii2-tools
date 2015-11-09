Yii2 tools
===================
Helpers, widgets for common web development

Actions
-------
* actions/FileDeleteAction - for deleting FileBehavior model files
* actions/FileRenameAction - for renaming FileBehavior model files
* actions/ListAction - for ajax response for DepDrop widget / or html input request

BEHAVIORS
---------
* behaviors/FileBehavior - for uploading files, saving, showing, processing images
* behaviors/RankBehavior - for managing model sorting field
* behaviors/SerializeBehavior - for saving JSON or serialized arrays

HELPERS
-------
* helpers/EventHelper - for processing events
* helpers/FormHelper - for processing form data
* helpers/GridHelper - for displaying GridView
* helpers/HtmlHelper - for displaying html elements
* helpers/MigrationHelper - for migration db operations
* helpers/RbacPermissionMigration - template (extend it) for rbac item add migration

TESTS
-----
* tests/FixtureManager - for generating fixtures

VALIDATORS
----------
* validators/ListValidator - for validating attribute having list of available values for it.

WIDGETS
-------
* widgets/ActiveForm - for using yii active form without echoing it.
* widgets/UrlView - for rendering any controller partial response (for rbac access to widget content).