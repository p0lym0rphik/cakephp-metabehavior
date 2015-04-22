[![Coverage Status](https://coveralls.io/repos/p0lym0rphik/cakephp-metabehavior/badge.png)](https://coveralls.io/r/p0lym0rphik/cakephp-metabehavior)
[![Build Status](https://travis-ci.org/p0lym0rphik/cakephp-metabehavior.svg?branch=master)](https://travis-ci.org/p0lym0rphik/cakephp-metabehavior)

CakePHP MetaBehavior
====================

[![Join the chat at https://gitter.im/p0lym0rphik/cakephp-metabehavior](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/p0lym0rphik/cakephp-metabehavior?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

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
<pre>
	// Just launch a find and datas are in the primary model.

	$model = $this->MyModel->findByid(1);
	
	return array(
		'MyModel' => array(
			'id' => 1,
			'foo' => 'bar'
		)
	)	
</pre>

3. You can directly search on meta values.
<pre>
	$this->MyModel->find('all',array(
		'conditions' => array(
			'MyModel.foo =' => 'bar'
		)
	));
</pre>

### Licence GPL2

CakePHP MetaBehavior plugin
Copyright (C) 2014  Fabien Moreau

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

