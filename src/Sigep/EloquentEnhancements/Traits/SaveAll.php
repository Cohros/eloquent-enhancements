<?php

namespace Sigep\EloquentEnhancements\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Sigep\Support\ArrayHelper;

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
     * @param  array $data
     * @return boolean
     */
    public function createAll(array $data = [])
    {
        $this->fill($data);

        if (!$this->save()) {
            return false;
        }

        $data = $this->fillForeignKeyRecursively($data);

        return $this->saveAll($data, true);
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
    public function saveAll(array $data = [], $skipUpdate = false)
    {
        $this->fill($data);
        if (!$skipUpdate && !$this->save()) {
            return false;
        }

        $relationships = $this->getRelationshipsFromData($data);

        // save relationships
        foreach ($relationships as $relationship => $values) {
            if (!$this->addRelated($relationship, $values)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add related data to the current model recursively
     * @param string $relationshipName
     * @param array $values
     * @return bool
     */
    public function addRelated($relationshipName, array $values)
    {
        // get info from relationship
        if (!method_exists($this, $relationshipName)) {
            return true;
        }

        $relationship = $this->$relationshipName();

        // if is a numeric array, recursive calls to add multiple related
        if (!ArrayHelper::isAssociative($values)) {
            foreach ($values as $value) {
                if (!$this->addRelated($relationshipName, $value)) {
                    return false;
                }
            }

            return true;
        }

        // if has not data, skipp
        if (ArrayHelper::isEmpty($values)) {
            return true;
        }

        // set foreign for hasMany relationships
        if ($relationship instanceof HasMany) {
            $values[$this->getForeignKey()] = $this->id;
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
        $model = $relationship->getRelated();

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
                $this->setErrors($obj->errors());
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
        }

        if (!$relationshipObject->createAll($values)) {
            $this->setErrors($relationshipObject->errors());
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
