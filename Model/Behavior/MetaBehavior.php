<?php
/**
 * MetaBehavior CakePHP Plugin
 * @author Moreau Fabien : dev@fabienmoreau.com
 */

class MetaBehavior extends ModelBehavior {

	public $attribute = false;

	public $schema = false;

	public $callbackValues = array();

	private $__currentJoins = array();

	/**
	* MetaBehavior::setup()
	* 
	* @param Model $model
	* @param array $config
	* @return boolean
	*/

	public function setup(Model $model, $config = array()) {
		// Loading Meta model to set find and save on the target table
		$this->Meta = ClassRegistry::init("MetaBehavior.Meta");
		// Storing current schema to compare with $this->data in beforeSave callback
		$this->schema = array_keys($model->schema());
		return true;
	}

	/**
	 * beforeFind Callback
	 *
	 * @param Model $model Model find is being run on.
	 * @param array $query Array of Query parameters.
	 * @return array Modified query
	 */

	public function beforeFind(Model $model, $query = array()) {
	
		# If there is no conditions, it's useless to looking for it !
		if (!array_key_exists('conditions',$query)) {
			return $query;
		}
		
		# Recursive function to find foreign field and build join table instead
		$query['conditions'] = $this->__replace_conditions($model, $query['conditions']);
		
		if (array_key_exists('joins',$query)) {
			$query['joins'] = array_merge($query['joins'], array_values($this->__currentJoins));
		} else {
			$query['joins'] = array_values($this->__currentJoins);
		}

		return $query;
	}

	/**
	 * Replace condition function
	 * Walk recursively to change meta value by join alias
	 * @param Model $model Model find is being run on.
	 * @param array $conditions Array of conditions query parameters.
	 * @return array Modified conditions
	 */

	private function __replace_conditions(Model $model, $conditions){

		foreach ($conditions as $cKey => $cValue) {
			if (is_array($cValue)) {
				// If is array, we check recursively
				$conditions[$cKey] = $this->__replace_conditions($model, $cValue);
			}

			$match = array();

			// Get the field attach to the condition
			$grep_field = preg_match("/".$model->alias."\.(\w+)/i", $cKey, $match);

			if ($grep_field) {
				$field = $match[1];

				if (!in_array($field, $this->schema)) {

					// If the join is not set yet, we build it
					if (!array_key_exists($field, $this->__currentJoins)) {
						$this->__currentJoins[$field] = array(
							'table' => 'metas',
							'alias' => $field,
							'type' => 'INNER',
							'conditions' => array(
								$field.'.foreignModel = "'.$model->alias.'"',
								$field.'.foreignKey = ' . $model->alias . '.' . $model->primaryKey,
								$field.'.meta_key = "'.$field.'"'
							)
						);
					}

					// Get the compare method
					$grep_operator = preg_match("/".$field."(.+)$/i", $cKey, $match);

					if ($grep_operator) {
						$operator = $match[1];
					} else {
						$operator = "";
					}

					// Set new condition operator and delete the old one
					$conditions[$field . '.meta_value' . $operator] = $cValue;

					unset($conditions[$cKey]);
				}
			}
		}

		return $conditions;
	}

	/**
	 * afterFind Callback
	 *
	 * @param Model $model Model find was run on
	 * @param array $results Array of model results.
	 * @param boolean $primary Did the find originate on $model.
	 * @return array Modified results
	 */

	public function afterFind(Model $model, $results, $primary = false) {

		// Check if we're in a array of results
		if (!empty($results) && isset($results[0][$model->alias])) {
			$primaryKeys = array();
			$rangeResults = array();

			// Get all primaryKeys to send just one Meta request for one search
			foreach ($results as $key => $result) {
				$rangeResults[$result[$model->alias][$model->primaryKey]] = $result;
				$primaryKeys[] = $result[$model->alias][$model->primaryKey];
			}

			// Get Metadatas
			$list = $this->Meta->find('all', array('conditions' => array(
				'Meta.foreignModel =' => $model->alias,
				'Meta.foreignKey' => $primaryKeys
			)));

			// Sort results table with metas
			foreach ($list as $l) {
				$v = $l['Meta']['meta_value'];
				$v = ($this->_is_serialized($v)) ? unserialize($v) : $v;
				$rangeResults[$l['Meta']['foreignKey']][$model->alias][$l['Meta']['meta_key']] = $v;
			}

			if ($model->findQueryType == 'first') {
				return array(0 => array_shift($rangeResults));
			}

			return $rangeResults;
		}

		return $results;
	}

	/**
	 * beforeSave Callback
	 *
	 * @param Model $model Model find was run on
	 * @param array $options Array of saving options.
	 * @return boolean status for continue save action
	 */

	public function beforeSave(Model $model, $options = array()) {
		// Storing data to save, compare to model schema, needed by afterSave callback
		foreach ($model->data[$model->alias] as $row => $value) {
			if (!in_array($row, $this->schema)) {
				$this->registerAttribute($model, $row, $value);
			}
		}
		return true;
	}

	/**
	 * registerAttribute function
	 * Convert data to persist
	 * @param Model $model Model find was run on
	 * @param string $row string with meta key.
	 * @param mixed $value to store.
	 * @return boolean result
	 */

	public function registerAttribute(Model $model, $row, $value) {
		if ($value != "" && is_array($value)) {
			$value = serialize($value);
		}

		$this->callbackValues[$row] = $value;

		return true;
	}

	/**
	 * setAttribute function
	 * Prepare datas to persist
	 * @param Model $model Model find was run on
	 * @param integer $foreignKey of the current model.
	 * @param string $key meta identifier.
	 * @param string $value meta to store
	 * @param boolean $primaryKey if add/editing value
	 * @return array attribute to persist
	 */

	public function setAttribute(Model $model, $foreignKey, $key, $value, $primaryKey = false) {

		$attr = array();

		if ($primaryKey !== false && is_numeric($primaryKey)) {
			$attr['Meta']['id'] = $primaryKey;
		}

		$attr['Meta']['foreignKey'] = $foreignKey;
		$attr['Meta']['foreignModel'] = $model->alias;
		$attr['Meta']['meta_key'] = $key;
		$attr['Meta']['meta_value'] = $value;

		return $attr;
	}

	/**
	 * afterSave callback
	 * Transaction to database
	 * @param Model $model Model find was run on
	 * @param boolean $created if add/edit
	 * @param array $options for saving configuration
	 * @return void
	 */

	public function afterSave(Model $model, $created, $options = array()) {
		if (!empty($this->callbackValues)) {
			$many = array();

			$previous_values = false;

			if (!$created) {
				$previous_values = $this->Meta->find('list',array(
					'conditions' => array('Meta.foreignModel =' => $model->alias, 'Meta.foreignKey =' => $model->id),
					'fields' => array('meta_key','id')
				));
			}

			foreach ($this->callbackValues as $key => $value) {
				$primary = false;

				if (is_array($previous_values) && array_key_exists($key, $previous_values)) {
					$primary = $previous_values[$key];
				}

				$many[] = $this->setAttribute($model, $model->id, $key, $value, $primary);
			}

			// Send transaction to meta table
			$this->Meta->saveMany($many);
		}

		$this->CallbackValues = array();
	}

	/**
	 * afterDelete callback
	 * Delete associate values of the primary model
	 * @param Model $model Model find was run on
	 * @return void
	 */

	public function afterDelete(Model $model) {
		$this->Meta->deleteAll(array('Meta.foreignKey =' => $model->id, 'Meta.foreignModel =' => $model->alias));
	}

	/**
	* _is_serialized function (protected)
	* Utility function to check if a string is serialized without generating exceptions
	* @param Model $model Model find was run on
	* @param mixed $data to check
	* @param boolean $strict to set the mode
	* @return boolean result
	*/

	protected function _is_serialized(Model $model, $data, $strict = true ) {
		if ( ! is_string( $data ) ) {
			return false;
		}
		$data = trim( $data );
		if ( 'N;' == $data ) {
			return true;
		}
		if ( strlen( $data ) < 4 ) {
			return false;
		}
		if ( ':' !== $data[1] ) {
			return false;
		}
		if ( $strict ) {
			$lastc = substr( $data, -1 );
			if ( ';' !== $lastc && '}' !== $lastc ) {
				return false;
			}
		} else {
			$semicolon = strpos( $data, ';' );
			$brace     = strpos( $data, '}' );
			if ( false === $semicolon && false === $brace )
				return false;

			if ( false !== $semicolon && $semicolon < 3 )
				return false;

			if ( false !== $brace && $brace < 4 )
				return false;
		}

		$token = $data[0];
		switch ( $token ) {
			case 's' :
				if ( $strict ) {
					if ( '"' !== substr( $data, -2, 1 ) ) {
						return false;
					}
				} elseif ( false === strpos( $data, '"' ) ) {
					return false;
				}
			case 'a' :
			case 'O' :
				return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
			case 'b' :
			case 'i' :
			case 'd' :
				$end = $strict ? '$' : '';
				return (bool) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
		}
		return false;
	}   
}