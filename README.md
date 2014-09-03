CakePHP MetaBehavior
====================

### Introduction

CakePHP Metabehavior is a meta data Behavior for CakePHP 2. 
This behavior allows you to store, retrieve and search for metadata about any record for any model within your database. 

### Installation

1. Add the folder on your app/Plugin directory.

2. Make sure that the Plugin is loaded (check your bootstrap configuration).

3. Run the following commands:
<pre>
cd /path/to/installation/
app/Console/cake schema create MetaBehavior.metas
</pre>

4. Add the behavior to the model you want to use.
<pre>
class MyModel extends Model {
	public $actsAs = array('Meta');
}</pre>

### How to 

1. Add datas to an object.
<pre>
// Just set meta key on the primary request model
	$savedDatas = $this->request->data;
	$savedDatas['MyModel']['foo'] = 'bar';
	
	$this->MyModel->save($savedDatas);
</pre>

2. Retrieve datas.
<pre>// Just launch a find and datas are in the primary model.
	/**
	*	return array(
	*		'MyModel' => array(
	*			'id' => 1,
	*			'foo' => 'bar'
	*		)
	*   )
	*/
	
	$model = $this->MyModel->findByid(1);
	
</pre>

3. You can directly search on meta values.
<pre>
	$this->MyModel->find('all',array(
		'conditions' => array(
			'MyModel.foo =' => 'bar'
		)
	));
</pre>
