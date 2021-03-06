<?php

namespace Sigep\EloquentEnhancements\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;

trait SaveAll
{
    /**
     * @see Model::fill
     * @param array $attributes
     * @return mixed
     */
    abstract function fill(array $attributes);

    /**
     * @see Model::save
     * @param array $options
     * @return mixed
     */
    abstract function save(array $options = []);

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    abstract function getForeignKey();

    /**
     * Checks if $options has a callable to do the validation.
     * If is provided, call the function and merge erros, if any
     * @param array $options
     * @param string $path
     * @return bool
     */
    private function __handleValidator(array $options, $path)
    {
        $modelName = get_class($this);
        $validator = null;
        $response = true;

        if (!empty($options[$modelName]['validator']) && is_callable($options[$modelName]['validator'])) {
            $validator = $options[$modelName]['validator'];
        } elseif (!empty($options['validator']) && is_callable($options['validator'])) {
            $validator = $options['validator'];
        }

        if ($validator) {
            $isValid = call_user_func($validator, $this);
            if ($isValid !== true) {
                $this->mergeErrors($isValid->toArray(), $path);
                $response = false;
            }
        }

        return $response;
    }

    /**
     * Checks if $options has restrictions about what can be filled and
     * filters $data
     * @param array $options
     * @param array $data
     */
    private function __handleFill(array $options, array $data)
    {
        $modelName = get_class($this);
        if (!empty($options[$modelName]['fillable'])) {
            $newData = [];
            foreach ($options[$modelName]['fillable'] as $field) {
                if (isset($data[$field])) {
                    $newData[$field] = $data[$field];
                }
            }
            $data = $newData;
        }

        $this->fill($data);
    }

    /**
     * create a new object and calls saveAll() method to save its relationships
     *
     * @param array $data
     * @param string $path used to control where put the error messages
     *
     * @return boolean
     */
    public function createAll(array $data = [], $options = [], $path = '')
    {
        $this->__handleFill($options, $data);
        $data = $this->checkBelongsTo($data, $options, $path);

        if ($this->errors()->count()) {
            return false;
        }

        if (!$this->__handleValidator($options, $path) || !$this->save()) {
            return false;
        }

        $data = $this->fillForeignKeyRecursively($data);

        return $this->saveAll($data, $options, true, $path);
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
    public function saveAll(array $data = [], array $options = [], $skipUpdate = false, $path = '')
    {
        $this->__handleFill($options, $data);
        $data = $this->checkBelongsTo($data, $options, $path); // @is really necessary?

        if ($this->errors()->count()) {
            return false;
        }

        if (!$skipUpdate) {
            if ($this->__handleValidator($options, $path) === false) {
                return false;
            }
            if (($this->save() === false) && ($this->isDirty())) {
                return false;
            }
        }

        $relationships = $this->getRelationshipsFromData($data);

        // save relationships
        foreach ($relationships as $relationship => $values) {
            $currentPath = $path ? "{$path}." : '';
            $currentPath .= $relationship;

            // check allowed amount of related objects
            // @todo this is the best way? maybe this must be on validation rules...?
            if ($this->checkRelationshipLimit($relationship, $values, $currentPath) === false) {
                return false;
            }

            if ($this->shouldDetachModels($relationship, $values)) {
                $this->$relationship()->withTimestamps()->detach();
            }

            if ($this->addRelated($relationship, $values, $options, $currentPath) === false) {
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

        if (isset($data[$foreign]) && $data[$foreign] == 'auto' && $this->getKey()) {
            $data[$foreign] = $this->getKey();
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
            $foreignKey = last(explode('.', $relationship->getQualifiedRelatedPivotKeyName()));
            if (isset($data[$foreignKey]) && is_array($data[$foreignKey])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if sync() should be used to create records on belongsToMany relationships
     *
     * @param string $relationship name of relationship
     * @param array $data data to check
     *
     * @return bool
     */
    private function shouldDetachModels($relationship, $data)
    {
        $relationship = $this->$relationship();
        if ($relationship instanceof BelongsToMany && count($data) >= 1 && is_array($data)) {
            foreach ($data as $element) {
                if (!is_array($element) || empty($element) || empty($element['id'])) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * This method is specific to create objects that are related with the current model on a
     * belongsTo relationship.
     * Useful to create a record that belongs to another record that don't exists yet.
     * This method will remove from $data data relative to belongsTo elements
     *
     * @param array $data
     * @param array $options
     * @param string $path
     * @return array
     */
    private function checkBelongsTo($data, array $options = [], $path = '') {
        $relationships = $this->getRelationshipsFromData($data);

        foreach ($relationships as $relationship => $values) {
            /** @var Relation $relationshipObject */
            $relationshipObject = $this->$relationship();

            if ($relationshipObject instanceof BelongsTo === false) {
                continue;
            }

            $currentPath = $path ? "{$path}." : '';
            $currentPath .= $relationship;

            $foreignKey = $relationshipObject->getForeignKey();
            $keyOtherObject = $relationshipObject->getOwnerKey();

            if (!empty($values[$keyOtherObject])) {
                $this->$foreignKey = (int) $values[$keyOtherObject];
            } else {
                $object = $relationshipObject->getRelated();
                if (!$object->createAll($values, $options)) {
                    $this->mergeErrors($object->errors()->toArray(), $currentPath);
                } else {
                    $this->$foreignKey = $object->getKey();
                }
            }

            unset($data[$relationship]);
        }

        return $data;
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
            $relatedObjectKey = $this->$relationship()->getRelated()->getKeyName();

            // check if is associative
            if ($values && $values !== array_values($values)) {
                return true; // @todo prevent this
            }

            foreach ($values as $key => $value) {
                $arrayIsEmpty = array_filter($value);
                if (empty($arrayIsEmpty)) {
                    unset($values[$key]);
                    continue;
                }

                if (!isset($value[$relatedObjectKey])) {
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
    public function addRelated($relationshipName, array $values, array $options = [], $path = '')
    {
        $relationship = $this->$relationshipName();

        // if is a numeric array, recursive calls to add multiple related
        if (ctype_digit(implode('', array_keys($values))) === true) {
            $position = 0;
            foreach ($values as $value) {
                if ($this->addRelated($relationshipName, $value, $options, $path . '.' . $position++) === false) {
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
            $values[last(explode('.', $relationship->getQualifiedForeignKeyName()))] = $this->getKey();
        }

        // if is MorphToMany, put other foreign and fill the type
        if ($relationship instanceof MorphMany) {
            $values[$relationship->getForeignKeyName()] = $this->getKey();
            $values[$relationship->getMorphType()] = get_class($this);
        }

        // if BelongsToMany, put current id in place
        if ($relationship instanceof BelongsToMany) {
            $values[last(explode('.', $relationship->getQualifiedForeignPivotKeyName()))] = $this->getKey();
            $belongsToManyOtherKey = last(explode('.', $relationship->getQualifiedRelatedPivotKeyName()));
        }

        // get target Model
        if ($relationship instanceof HasManyThrough) {
            $model = $relationship->getParent();
        } else {
            $model = $relationship->getRelated();
        }

        // if has ID, delete or update
        if (!empty($values[$model->getKeyName()]) && $relationship instanceof BelongsToMany === false) {
            $obj = $model->find($values[$model->getKeyName()]);
            if (!$obj) {
                return false; // @todo transport error
            }

            // delete or update?
            if (!empty($values['_delete'])) {
                return $obj->delete();
            }

            if (!$obj->saveAll($values, $options)) {
                $this->mergeErrors($obj->errors()->toArray(), $path);
                return true;
            }

            return true;
        }

        if (!empty($values['_delete']) && empty($values[$model->getKeyName()])) {
            // ignore
            return null;
        }

        // only BelongsToMany :)
        if (!empty($values['_delete']) && $relationship instanceof BelongsToMany) {
            $this->$relationshipName()->detach($values[last(explode('.', $relationship->getQualifiedRelatedPivotKeyName()))]);
            return true;
        }


        if ((isset($belongsToManyOtherKey) && empty($values[$belongsToManyOtherKey]))) {
            $obj = $relationship->getRelated();
            // if has conditions, fill the values
            // this helps to add static values in relationships using its conditions
            // @todo experimental
            foreach ($relationship->getQuery()->getQuery()->wheres as $where) {
                $column = last(explode('.', $where['column']));
                if (!empty($where['value']) && empty($values[$column])) {
                    $values[$column] = $where['value'];
                }
            }

            if (empty($values[$obj->getKeyName()])) {
                if (!$obj->createAll($values, $options)) {
                    $this->mergeErrors($obj->errors()->toArray(), $path);
                    return false;
                }
                $values[$belongsToManyOtherKey] = $obj->getKey();
            }

        }

        if ($relationship instanceof HasMany || $relationship instanceof MorphMany) {
            $relationshipObject = $relationship->getRelated();
        } elseif ($relationship instanceof BelongsToMany) {
            // if has a relationshipModel, use the model. Else, use attach
            // attach doesn't return nothing :(
            if (empty($this->relationshipsModels[$relationshipName])) {
                $field = last(explode('.', $relationship->getQualifiedRelatedPivotKeyName()));
                if (!isset($values[$field])) {
                    $field = 'id'; // default
                }
                $this->$relationshipName()->attach($values[$field]);

                return true;
            }

            $relationshipObjectName = $this->relationshipsModels[$relationshipName];
            $relationshipObject = new $relationshipObjectName;
            $relationshipObjectKeyName = $relationshipObject->getKeyName();

            if (!empty($values[$relationshipObjectKeyName])) {
                if (!empty($values[$belongsToManyOtherKey])) {
                    $relationshipObject = $relationshipObjectName::find($values[$relationshipObjectKeyName]);
                } else {
                    $relationshipObject = $relationship->getRelated()->find($values[$relationshipObjectKeyName]);

                    if (!empty($relationshipObject)) {
                        $this->$relationshipName()->withTimestamps()->attach($values[$relationshipObjectKeyName]);
                    }
                }

                if (!$relationshipObject) {
                    $relationshipObject = new $relationshipObjectName; // @todo check this out
                }
            }
        } elseif ($relationship instanceof HasManyThrough) {
            $relationshipObject = $model;
        }

        $useMethod = (empty($values[$relationshipObject->getKeyName()])) ? 'createAll' : 'saveAll';
        if (!$relationshipObject->$useMethod($values, $options)) {
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
     * @param string $path
     */
    protected function mergeErrors(array $objErrors, $path)
    {
        $thisErrors = $this->errors();
        if ($path) {
            $path .= '.';
        }
        foreach ($objErrors as $field => $errors) {
            foreach ($errors as $error) {
                $thisErrors->add(
                    "{$path}{$field}",
                    $error
                );
            }
        }
        $this->setErrors($thisErrors);
    }
}
