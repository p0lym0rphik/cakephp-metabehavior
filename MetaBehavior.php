<?php

/**
 * @author Moreau Fabien : fmoreau.go@gmail.com
 */
 
class MetaBehavior extends ModelBehavior {

    /**
     * MetaBehavior::setup()
     * 
     * @param mixed $Model
     * @param mixed $config
     * @return void
     */
     
    public $Attribute = false;
    public $CallbackValues = array();

    public function setup(Model $Model, $config = array()) {
        $this->Meta = ClassRegistry::init('Meta');
        $this->Meta->locale = Configure::read('Config.languages');
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
        if (!in_array($model->findQueryType, array('list', 'count')) && $model->recursive > 0) {
            foreach ($results as $key => $result) {
                if (isset($result[$model->alias][$model->primaryKey])) {
                    $list = $this->Meta->find('all', array('conditions' => array(
                            'Meta.model =' => $model->alias,
                            'Meta.foreignKey =' => $result[$model->alias][$model->primaryKey]
                    )));
                    foreach ($list as $l) {
                        $v = $l['Meta']['value'];
                        $v = (@unserialize($v) !== false) ? unserialize($v) : $v;
                        $results[$key][$model->alias][$l['Meta']['key']] = $v; 
                    }
                }
            }
        }
        return $results;
    }

    public function beforeSave(Model $model, $options = array()) {
        $schema = $model->schema();
        $schema = array_keys($schema);
        foreach ($model->data[$model->alias] as $row => $value) {
            if (!in_array($row, $schema)) {
                $this->registerAttribute($model, $row, $value);
            }
        }
        return true;
    }

    public function registerAttribute(Model $model, $row, $value) {
        if ($value != "" && is_array($value) && isset($value['tmp_name'])){
            if($value['tmp_name'] != '') {
                $return = $this->upload($value);
                if ($return !== false) {
                    $value = $return;
                }
            } else {
                return true;
            }
        }elseif($value != "" && is_array($value)){
            $value = serialize($value);
        }

        $this->CallbackValues[$row] = $value;

        return true;
    }
    
    
    public function upload($entry) {
        if ($entry['error'] == 0) {
            if ($entry['size'] <= Configure::read('Config.max_input_size')) {
                $infos = pathinfo($entry['name']);
                $extension_upload = $infos['extension'];
                if (in_array($extension_upload, Configure::read('Config.allowed_file_extensions'))) {
                    $microtimename = str_replace(".", "", microtime(true)) . rand(0, 10000) . "." . $infos['extension'];
                    move_uploaded_file($entry['tmp_name'], Configure::read('Config.upload_directory') . $microtimename);
                    return $microtimename;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function setAttribute(Model $model, $primaryKey, $key, $value) {
        $already_set = $this->Meta->find(
                'first', array('conditions' =>
            array('Meta.model =' => $model->alias, 'Meta.foreignKey =' => $primaryKey, 'Meta.key =' => $key))
        );

        $attr = array();

        if ($already_set) {
            $attr['Meta']['id'] = $already_set['Meta']['id'];
        } else {
            $this->Meta->create();
        }

        $attr['Meta']['foreignKey'] = $primaryKey;
        $attr['Meta']['model'] = $model->alias;
        $attr['Meta']['key'] = $key;
        $attr['Meta']['value'] = $value;
        return $this->Meta->saveAll($attr);
    }

    public function get_entity_metas(Model $model, $foreignKey, $foreignModel = null) {
        return $this->Meta->find(
          'list', array('fields' => array('key', 'value'), 'conditions' => array('Meta.foreignKey =' => $foreignKey, 'Meta.model =' => $foreignModel))
        );
    }

    public function afterSave(Model $model, $created, $options = array()) {
        if (!empty($this->CallbackValues)) {
            foreach ($this->CallbackValues as $key => $value) {
                $this->setAttribute($model, $model->id, $key, $value);
            }
        }
        
        $this->CallbackValues = array();
    }

    public function afterDelete(Model $model) {
        $this->Meta->deleteAll(array('conditions' => array('Meta.foreignKey =' => $model->id, 'Meta.model =' => $model->alias)));
    }

    public function search(Model $model, $conditions = array()) {
        if (!empty($conditions)) {
            foreach ($conditions as $key => $value) {
                if (is_array($value)) {
                    $val = $value['value'];
                    $compare = $value['compare'];
                } else {
                    $val = $value;
                    $compare = "";
                }

                $conditions["AND"][] = array(
                    "Attribute.key =" => $key,
                    "Attribute.value " . $compare => $val
                );
            }
        }

        $this->Meta->find('list', array("fields" => array("object_id"), "conditions" => $conditions));

        return $Catalog;
    }

}
