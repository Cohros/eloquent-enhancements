<?php

namespace Sigep\EloquentEnhancements\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\MessageBag;

trait SaveAll
{
    /**
     * Put the id of current object as foreign key in all arrays inside $data
     * Util when a relationship of a relationship depends of the id from current
     * model as foreign key to.
     * To avoid problems, data must to have the foreign key with "auto" value.
     * Just in case that the records belongs, for any reason, to another object
     *
     * @param array $data changed data
     *
     * @return array
     */
    private function fillForeignKeyRecursively(array $data)
    {
        $foreign = $this->getForeignKey();

        foreach ($data as &$piece) {
            if (is_array($piece)) {
                $piece = $this->fillForeignKeyRecursively($piece);
            }
        }

        if (isset($data[$foreign]) && $data[$foreign] == 'auto' && $this->id) {
            $data[$foreign] = $this->id;
        }

        return $data;
    }

    /**
     * Determines if sync() should be used to create records on belongsToMany relationships
     *
     * @param string $relationship name of relationship
     * @param array $data data to check
     *
     * @return bool
     */
    private function shouldUseSync($relationship, $data)
    {
        $relationship = $this->$relationship();
        if ($relationship instanceof BelongsToMany && count($data) === 1) {
            // check foreign key
            $foreignKey = end(explode('.', $relationship->getOtherKey()));
            if (isset($data[$foreignKey]) && is_array($data[$foreignKey])) {
                return true;
            }
        }

        return false;
    }

    /**
     * create a new object and calls saveAll() method to save its relationships
     *
     * @param array $data
     * @param string $path used to control where put the error messages
     *
     * @return boolean
     */
    public function createAll(array $data = [], $path = '')
    {
        $this->fill($data);

        if (!$this->save()) {
            return false;
        }

        $data = $this->fillForeignKeyRecursively($data);

        return $this->saveAll($data, true, $path);
    }

    /**
     * Update current record and create/update its related data
     * The related data must be array and the key is the name of the relationship
     * We support relationships from relationships too.
     *
     * @param  array $data
     * @param  boolean $skipUpdate if true, current model will not be updated
     * @return boolean
     */
    public function saveAll(array $data = [], $skipUpdate = false, $path = '')
    {
        $this->fill($data);
        if (!$skipUpdate && !$this->save()) {
            return false;
        }

        $relationships = $this->getRelationshipsFromData($data);

        // save relationships
        foreach ($relationships as $relationship => $values) {
            $currentPath = $path ? "{$path}." : '';
            $currentPath .= $relationship;

            // check allowed amount of related objects
            if ($this->checkRelationshipLimit($relationship, $values, $currentPath) === false) {
                return false;
            }

            if (!$this->addRelated($relationship, $values, $currentPath)) {
                return false;
            }
        }
        
        // search for relationships that has limit and no data was send, to apply the minimum validation
        if (isset($this->relationshipsLimits)) {
            $relationshipsLimits = $this->relationshipsLimits;
            $checkRelationships = array_diff(array_keys($relationshipsLimits), array_keys($relationships));
            
            foreach ($checkRelationships as $checkRelationship) {
                $currentPath = $path ? "{$path}." : '';
                $currentPath .= $checkRelationship;
                
                if (!$this->checkRelationshipLimit($checkRelationship, [], $currentPath)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the specified limit for $relationship or false if not exists
     * @param $relationship name of the relationship
     * @return mixed
     */
    protected function getRelationshipLimit($relationship)
    {
        if (isset($this->relationshipsLimits[$relationship])) {
            return array_map (
                'intval',
                explode(':', $this->relationshipsLimits[$relationship])
            );
        }

        return false;
    }

    /**
     * Checks if amount of related objects are allowed
     * @param string $relationship relationship name
     * @param array $values
     * @param string $path
     * @return array modified $values
     */
    protected function checkRelationshipLimit($relationship, $values, $path)
    {
        $relationshipLimit = $this->getRelationshipLimit($relationship);
        if (!$relationshipLimit) {
            return $values;
        }

        if (count($values) === 1 && $this->shouldUseSync($relationship, $values)) {
            $sumRelationships = count(end($values));
        } else {
            $this->load($relationship);
            $currentRelationships = $this->$relationship->count();
            $newRelationships = 0;
            $removeRelationships = [];

            // check if is associative
            if ($values && ctype_digit(implode('', array_keys($values))) === false) {
                return true; // @todo prevent this
            }

            foreach ($values as $key => $value) {
                $arrayIsEmpty = array_filter($value);
                if (empty($arrayIsEmpty)) {
                    unset($values[$key]);
                    continue;
                }

                if (!isset($value['id'])) {
                    $newRelationships++;
                    $removeRelationships[] = $key;
                }
            }

            $sumRelationships = $currentRelationships + $newRelationships;
        }

        $this->errors();
        if ($sumRelationships < $relationshipLimit[0]) {
            $this->errors->add($path, 'validation.min', $relationshipLimit[0]);
        }
        
        if ($sumRelationships > $relationshipLimit[1]) {
            $this->errors->add($path, 'validation.max', $relationshipLimit[1]);
        }
        
        if ($this->errors->has($path)) {
            return false;
        }
        
        return true;
    }

    /**
     * Add related data to the current model recursively
     * @param string $relationshipName
     * @param array $values
     * @return bool
     */
    public function addRelated($relationshipName, array $values, $path = '')
    {
        $relationship = $this->$relationshipName();

        // if is a numeric array, recursive calls to add multiple related
        if (ctype_digit(implode('', array_keys($values))) === true) {
            $position = 0;
            foreach ($values as $value) {
                if (!$this->addRelated($relationshipName, $value, $path . '.' . $position++)) {
                    return false;
                }
            }

            return true;
        }

        // if has not data, skip
        $arrayIsEmpty = array_filter($values);
        if (empty($arrayIsEmpty)) {
            return true;
        }

        if ($this->shouldUseSync($relationshipName, $values)) {
            $this->$relationshipName()->sync(end($values));
            return true;
        }

        // set foreign for hasMany relationships
        if ($relationship instanceof HasMany) {
            $values[end(explode('.', $relationship->getForeignKey()))] = $this->id;
        }

        // if is MorphToMany, put other foreign and fill the type
        if ($relationship instanceof MorphMany) {
            $values[$relationship->getPlainForeignKey()] = $this->id;
            $values[$relationship->getPlainMorphType()] = get_class($this);
        }

        // if BelongsToMany, put current id in place
        if ($relationship instanceof BelongsToMany) {
            $values[end(explode('.', $relationship->getForeignKey()))] = $this->id;
            $belongsToManyOtherKey = end(explode('.', $relationship->getOtherKey()));
        }

        // get targetModel
        if ($relationship instanceof HasManyThrough) {
            $model = $relationship->getParent();
        } else {
            $model = $relationship->getRelated();
        }

        // if has ID, delete or update
        if (!empty($values['id'])) {
            $obj = $model->find($values['id']);
            if (!$obj) {
                return false; // @todo transport error
            }

            // delete or update?
            if (!empty($values['_delete'])) {
                return $obj->delete();
            }
            
            if (!$obj->saveAll($values)) {
                $this->mergeErrors($obj->errors()->toArray(), $path);
                return true;
            }

            return true;
        }

        // only BelongsToMany :)
        if (!empty($values['_delete'])) {
            $this->$relationshipName()->detach($values[end(explode('.', $relationship->getOtherKey()))]);
            return true;
        }

        if (isset($belongsToManyOtherKey) && empty($values[$belongsToManyOtherKey])) {
            $obj = $relationship->getRelated();

            // if has conditions, fill the values)
            // this helps to add fixed values in relationships using its conditions
            // @todo experimental
            foreach ($relationship->getQuery()->getQuery()->wheres as $where) {
                $column = end(explode('.', $where['column']));
                if (!empty($where['value']) && empty($values[$column])) {
                    $values[$column] = $where['value'];
                }
            }

            if (!$obj->createAll($values)) {
                $this->mergeErrors($obj->errors()->toArray(), $path);
                return false;
            }

            $values[$belongsToManyOtherKey] = $obj->id;
        }

        if ($relationship instanceof HasMany || $relationship instanceof MorphMany) {
            $relationshipObject = $relationship->getRelated();
        } elseif ($relationship instanceof BelongsToMany) {
            // if has a relationshipModel, use the model. Else, use attach
            // attach doesn't return nothing :(
            if (empty($this->relationshipsModels[$relationshipName])) {
                $field = end(explode('.', $relationship->getOtherKey()));
                $this->$relationshipName()->attach($values[$field]);
                return true;
            }

            $relationshipObjectName = $this->relationshipsModels[$relationshipName];
            $relationshipObject = new $relationshipObjectName;
        } elseif ($relationship instanceof HasManyThrough) {
            $relationshipObject = $model;
        }

        if (!$relationshipObject->createAll($values)) {
            $this->mergeErrors($relationshipObject->errors()->toArray(), $path);
            return false;
        }

        return true;
    }

    /**
     * get values that are array in $data
     * use this function to extract relationships from Input::all(), for example
     * @param  array $data
     * @return array
     */
    public function getRelationshipsFromData(array $data = [])
    {
        $relationships = [];

        foreach ($data as $key => $value) {
            if (is_array($value) && !is_numeric($key) && method_exists($this, $key)) {
                $relationships[$key] = $value;
            }
        }

        return $relationships;
    }
    
    /**
     * Merge $objErrors with $this->errors using $path
     * 
     * @param array $objErrors
     * @param type $path
     */
    protected function mergeErrors(array $objErrors, $path)
    {
        $thisErrors = $this->errors();
        foreach ($objErrors as $field => $errors) {
            foreach ($errors as $error) {
                $thisErrors->add(
                    "{$path}.{$field}",
                    $error
                );
            }
        }
        $this->setErrors($thisErrors);
    }
}
