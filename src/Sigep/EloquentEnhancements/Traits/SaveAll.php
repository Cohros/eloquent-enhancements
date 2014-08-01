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
     * Util when a relationship of a relationship depends of the id from current model
     * as foreign key to.
     * To avoid problems, data must to have the foreign key with "auto" value. Just in case
     * that the records belongs, for any reason, to another object
     * @param array $data
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
     * create a new object and calls saveAll() method to save its relationships
     * @param array $data
     * @param string $path used to control where put the error messages
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
     * @param  boolean $skipUpdate if true, current model will not be changed, just the relationships
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

            // dont add more relationships that is allowed
            $values = $this->applyRelationshipLimit($relationship, $values);

            if (!$this->addRelated($relationship, $values, $currentPath)) {
                return false;
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
            return $this->relationshipsLimits[$relationship];
        }

        return false;
    }

    /**
     * Remove values from $values when the relationship limit is reached
     * @param string $relationship relationship name
     * @param array $values
     * @return array modified $values
     */
    protected function applyRelationshipLimit($relationship, $values)
    {
        $relationshipLimit = $this->getRelationshipLimit($relationship);
        if (!$relationshipLimit) {
            return $values;
        }

        $currentRelationships = count($this->$relationship);
        $newRelationships = 0;
        $removeRelationships = [];

        // check if is associative
        $arrayKeys = array_keys($values);
        $arrayKeys = implode('', $arrayKeys);
        if (ctype_digit($arrayKeys) === false) {
            return $values;
        }

        foreach ($values as $key => $value) {
            $arrayIsEmpty = array_filter($value);
            $arrayIsEmpty = empty($arrayIsEmpty);
            if ($arrayIsEmpty) {
                unset($values[$key]);
                continue;
            }

            if (!isset($value['id'])) {
                $newRelationships++;
                $removeRelationships[] = $key;
            }
        }

        $diff = ($currentRelationships + $newRelationships) - $relationshipLimit;
        if ($diff) {
            $removeRelationships = array_slice($removeRelationships, -$diff);
            foreach ($removeRelationships as $key) {
                unset($values[$key]);
            }
        }

        return array_values($values);
    }

    /**
     * Add related data to the current model recursively
     * @param string $relationshipName
     * @param array $values
     * @return bool
     */
    public function addRelated($relationshipName, array $values, $path = '')
    {
        // get info from relationship
        if (!method_exists($this, $relationshipName)) {
            return true;
        }

        $relationship = $this->$relationshipName();

        // if is a numeric array, recursive calls to add multiple related
        $arrayKeys = array_keys($values);
        $arrayKeys = implode('', $arrayKeys);
        if (ctype_digit($arrayKeys) === true) {
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
        $arrayIsEmpty = empty($arrayIsEmpty);
        if ($arrayIsEmpty) {
            return true;
        }

        // set foreign for hasMany relationships
        if ($relationship instanceof HasMany) {
            $values[last(explode('.', $relationship->getForeignKey()))] = $this->id;
        }

        // if is MorphToMany, put other foreign and fill the type
        if ($relationship instanceof MorphMany) {
            $values[$relationship->getPlainForeignKey()] = $this->id;
            $values[$relationship->getPlainMorphType()] = get_class($this);
        }

        // if BelongsToMany, put current id in place
        if ($relationship instanceof BelongsToMany) {
            $values[last(explode('.', $relationship->getForeignKey()))] = $this->id;
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
                return false;
            }

            // delete or update?
            if (!empty($values['_delete'])) {
                $resultAction = $obj->delete();
            } else {
                // @todo put errors
                $resultAction = $obj->saveAll($values);
            }

            return $resultAction;
        }

        // only BelongsToMany :)
        if (!empty($values['_delete'])) {
            $this->$relationshipName()->detach($values[last(explode('.', $relationship->getOtherKey()))]);
            return true;
        }

        if (!empty($values['_create']) && $relationship instanceof BelongsToMany) {
            $obj = $relationship->getRelated();

            // if has conditions, fill the values)
            // this helps to add fixed values in relationships using its conditions
            // @todo experimental
            foreach ($relationship->getQuery()->getQuery()->wheres as $where) {
                $column = last(explode('.', $where['column']));
                if (!empty($where['value']) && empty($values[$column])) {
                    $values[$column] = $where['value'];
                }
            }

            if (!$obj->createAll($values)) {
                $objErrors = $obj->errors()->toArray();
                $thisErrors = $this->errors();
                foreach ($objErrors as $field => $errors) {
                    foreach ($errors as $error ) {
                        $thisErrors->add(
                            "{$path}.{$field}",
                            $error
                        );
                    }
                }
                $this->setErrors($thisErrors);
                return false;
            }

            $values[last(explode('.', $relationship->getOtherKey()))] = $obj->id;
        }

        if ($relationship instanceof HasMany || $relationship instanceof MorphMany) {
            $relationshipObject = $relationship->getRelated();
        } elseif ($relationship instanceof BelongsToMany) {
            // if has a relationshipModel, use the model. Else, use attach
            // attach doesn't return nothing
            if (empty($this->relationshipsModels[$relationshipName])) {
                $field = last(explode('.', $relationship->getOtherKey()));
                $this->$relationshipName()->attach($values[$field]);
                return true;
            }

            $relationshipObject = $this->relationshipsModels[$relationshipName];
            $relationshipObject = new $relationshipObject;
        } elseif ($relationship instanceof HasManyThrough) {
            $relationshipObject = $model;
        }

        if (!$relationshipObject->createAll($values)) {
            $objErrors = $relationshipObject->errors()->toArray();
            $thisErrors = $this->errors();
            if (! $thisErrors instanceof MessageBag) {
                $thisErrors = new MessageBag();
            }
            foreach ($objErrors as $field => $errors) {
                foreach ($errors as $error ) {
                    $thisErrors->add(
                        "{$path}.{$field}",
                        $error
                    );
                }
            }
            $this->setErrors($thisErrors);
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
            if (is_array($value) && !is_numeric($key)) {
                $relationships[$key] = $value;
            }
        }

        return $relationships;
    }
}
