CakePHP meta-behavior
=============

I) Introduction

Sometimes you can have some cases where your model must have a lot of different attributes, like product object, different field name for the same model or inheritance system.
Before jumping directly with NoSQL structure there is other solution like metadatas management.

II) Methodology

Process is simple, beforeSave callback compare input datas with the current schema and save it in another table. After find callback find metadata and merge width the current model result.

III) How to use

Simply declare the Meta Behavior in your model.

class Foo extends Bar{
  public $actsAs = array("Meta");
}
